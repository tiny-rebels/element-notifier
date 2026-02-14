<?php

namespace Element\Notifier\providers;

use Element\Notifier\Service;

use Element\Notifier\exceptions\{
    CurlErrorException,
    HttpException,
    InvalidPriorityException,
    InvalidSendtimeException
};

use Element\Notifier\helpers\PendingMessage;

/**
 * GatewayAPI provider.
 *
 * Supports:
 *  - sendMessage() → now()
 *  - sendMessage() → schedule(int $unixTimestamp)
 *
 * Scheduling uses the REST-field "sendtime", documented by GatewayAPI:
 * sendtime (integer) – Unix timestamp used to schedule message sending. [1](https://apitextmagic.voog.com/https-api/examples)
 */
class GatewayAPI extends Service {

    /**
     * Prepares the text-message and returns a PendingMessage-objekt,
     * so that the developer can chain:
     *
     *   ->sendMessage(...)->now()
     *   ->sendMessage(...)->schedule($timestamp)
     *
     * @param array $recipients     List of msisdn (f.ex. ["+4522334455"])
     * @param string $message       SMS-text
     * @param string $sender        Sendername / ID (max 11 alfanumeric)
     * @param string $priority      BULK|NORMAL|URGENT|VERY_URGENT (default = NORMAL)
     *
     * @return PendingMessage
     */
    public function sendMessage(array $recipients = [], string $message = "", string $sender = "element-notifier", string $priority = "NORMAL") {

        return new PendingMessage($this, $recipients, $message, $sender, $priority);
    }

    /**
     * Performs the HTTP-call to GatewayAPI REST.
     *
     * Supports scheduling via $sendAt, that maps to "sendtime".
     *
     * @param array         $recipients
     * @param string        $message        Content of what message you want to send
     * @param string        $sender         Either a name or a phonenumber
     * @param string        $priority       BULK|NORMAL|URGENT|VERY_URGENT
     * @param int|null      $sendAt         UNIX timestamp (seconds)
     * @param string|null   $callbackUrl    (REST: callback_url)
     * @param string|null   $payload        (REST: payload – Base64, ekskluderer 'message' og 'tags')
     *
     * @return mixed|null                   message IDs from the provider response
     *
     * @throws \RuntimeException            either by HTTP- or cURL-error
     */
    public function dispatch(array $recipients, string $message, string $sender, string $priority, ?int $sendAt = null, $callbackUrl = null, $payload = null) {

        $url       = $this->url;
        $api_token = $this->apiToken;

        // Validates priority against the GatewayAPI’s allowed values
        $allowed = ['BULK', 'NORMAL', 'URGENT', 'VERY_URGENT'];

        $priority = strtoupper($priority);

        if (!in_array($priority, $allowed, true)) {

            throw new InvalidPriorityException($priority, $allowed);
        }

        $json = [

            'encoding'   => "UCS2", // lets you use æ/ø/å/ and emoji's
            'sender'     => $sender,
            'message'    => $message,
            'recipients' => [],
            'priority'   => $priority,
        ];

        foreach ($recipients as $msisdn) {

            $json['recipients'][] = ['msisdn' => $msisdn];
        }

        // Scheduling: GatewayAPI official parameter "sendtime" (UNIX seconds) [1](https://apitextmagic.voog.com/https-api/examples)
        if ($sendAt !== null) {

            if (!is_int($sendAt) || $sendAt <= time()) {

                throw new InvalidSendtimeException($sendAt);
            }

            $json['sendtime'] = $sendAt;
        }

        if (is_string($callbackUrl) && $callbackUrl !== '') {

            $json['callback_url'] = $callbackUrl; // documented field for status webhooks [1](https://apitextmagic.voog.com/https-api/examples)
        }

        // MESSAGE vs PAYLOAD (mutually exclusive according to REST)
        if ($payload !== null && $payload !== '') {

            // Binary SMS → payload is Base64, and 'message' is therefor NOT ALLOWED. [1](https://apitextmagic.voog.com/https-api/examples)
            $json['payload'] = $payload;

        } else {

            // Almindelig tekst SMS → brug 'message'
            $json['message'] = $message;
        }

        // cURL-call
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_USERPWD, $api_token . ":");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        $errstr = $errno !== 0 ? curl_error($ch) : null;
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);

        if ($errno !== 0) {

            throw new CurlErrorException($errno, $errstr);
        }

        if ($status < 200 || $status >= 300) {

            $body = is_string($result) ? $result : null;

            throw new HttpException($status, $body);
        }

        $decoded = json_decode($result);

        return $decoded->ids ?? null;
    }


    /**
     * Fetch an SMS, and it's status from GatewayAPI via GET /rest/mtsms/{id}
     * Docs: "Get SMS and SMS status" (HTTP 200 med JSON) [1](https://apitextmagic.voog.com/https-api/examples)
     *
     * @param int   $id     Message-ID from GatewayAPI (from 'ids' after send/schedule)
     * @param bool  $assoc  true => returns an assoc array; false => stdClass
     *
     * @return array|object  The JSON-decoded respons (typically a list with ONE SMS)
     *
     * @throws \InvalidArgumentException
     * @throws CurlErrorException
     * @throws HttpException
     */
    public function getTextMessage(int $id, bool $assoc = true) {

        if (!is_int($id) || $id <= 0) {

            throw new \InvalidArgumentException("The 'id' must be a positive integer.");
        }

        $base     = rtrim($this->url, '/');   // fx https://gatewayapi.com/rest/mtsms
        $endpoint = $base . '/' . $id;
        $token    = $this->apiToken;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
        curl_setopt($ch, CURLOPT_USERPWD, $token . ":");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        $errstr = $errno !== 0 ? curl_error($ch) : null;
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);

        if ($errno !== 0) {

            throw new CurlErrorException($errno, $errstr);
        }

        if ((int)$status !== 200) {

            $body = is_string($result) ? $result : null;
            throw new HttpException((int)$status, $body);
        }

        // Returns an assoc array (default) or stdClass
        return json_decode($result, (bool)$assoc);
    }

    /**
     * (Optionally helper) Fetch deliverystatus pr. recipient in a text.
     * Parses the response from getSms(...) and extracts recipients[*].msisdn/dsnstatus/dsntime.
     * The strukture follows GET /rest/mtsms/{id} found in docs. [1](https://apitextmagic.voog.com/https-api/examples)
     *
     * @param int $id
     * @return array  Fx: [ ['msisdn' => '4512345678', 'dsnstatus' => 'DELIVERED', 'dsntime' => 1498040129.0], ... ]
     */
    public function getTextMessageStatuses(int $id): array {

        $data = $this->getTextMessage($id, true); // assoc array

        // Response is typically a list (array) with one message
        if (!is_array($data)) {

            return [];
        }

        // Find the first element, that looks like a recipient
        $message = null;

        if (isset($data[0]) && is_array($data[0])) {

            $message = $data[0];

        } elseif (!empty($data) && isset($data['recipients'])) {

            $message = $data; // fallback IF the API one day returns an objekt
        }

        if (!$message || !isset($message['recipients']) || !is_array($message['recipients'])) {

            return [];
        }

        $data = [];

        foreach ($message['recipients'] as $recipient) {

            $data[] = [

                'dsnerror'      => isset($recipient['dsnerror'])        ? (string)$recipient['dsnerror']        : null,
                'dsnerrorcode'  => isset($recipient['dsnerrorcode'])    ? (string)$recipient['dsnerrorcode']    : null,
                'dsnstatus'     => isset($recipient['dsnstatus'])       ? (string)$recipient['dsnstatus']       : null,
                'dsntime'       => isset($recipient['dsntime'])         ? $recipient['dsntime']                 : null,
                'mcc'           => isset($recipient['mcc'])             ? (string)$recipient['mcc']             : null,
                'mnc'           => isset($recipient['mnc'])             ? (string)$recipient['mnc']             : null,
                'msisdn'        => isset($recipient['msisdn'])          ? (string)$recipient['msisdn']          : null,
                'senttime'      => isset($recipient['senttime'])        ? $recipient['senttime']                : null,
            ];
        }

        return $data;
    }

    /**
     * Deletes a scheduled (not-yet-performed) SMS from the GatewayAPI-queue.
     *
     * This method ONLY WORKS for texts, that are created with the 'sendtime'-parameter (scheduled).
     * Docs: DELETE /rest/mtsms/{id}  →  204 No Content ved succes. [1](https://apitextmagic.voog.com/https-api/examples)
     *
     * @param int $id  ID from the GatewayAPI-response when you created the message.
     * @return bool    true, if the deletion went well (HTTP 204).
     *
     * @throws CurlErrorException  by cURL-/transport error
     * @throws HttpException       by HTTP-status ≠ 204
     */
    public function deleteScheduledMessage($id): bool {

        if (!is_int($id) || $id <= 0) {

            throw new \InvalidArgumentException("The 'id' must be a positive integer.");
        }

        $url       = rtrim($this->url, '/');   // fx https://gatewayapi.com/rest/mtsms
        $api_token = $this->apiToken;

        // Build endpoint: {base}/{id}
        $endpoint = $url . '/' . $id;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
        curl_setopt($ch, CURLOPT_USERPWD, $api_token . ":");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        $errstr = $errno !== 0 ? curl_error($ch) : null;
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);

        if ($errno !== 0) {

            throw new CurlErrorException($errno, $errstr);
        }

        // Succes: 204 No Content
        if ((int)$status === 204) {

            return true;
        }

        // Error from the server: throws HttpException with status + body
        $body = is_string($result) ? $result : null;

        throw new HttpException($status, $body);
    }

}