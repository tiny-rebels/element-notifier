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
     * @param array     $recipients
     * @param string    $message        Content of what message you want to send
     * @param string    $sender         Either a name or a phonenumber
     * @param string    $priority       BULK|NORMAL|URGENT|VERY_URGENT
     * @param int|null  $sendAt         UNIX timestamp (seconds)
     *
     * @return mixed|null               message IDs from the provider response
     *
     * @throws \RuntimeException        either by HTTP- or cURL-error
     */
    public function dispatch(array $recipients, string $message, string $sender, string $priority, ?int $sendAt = null) {

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
}