<?php

namespace Element\Notifier\providers;

use Element\Notifier\Service;

class GatewayAPI extends Service {

    /*----------------------------------------------*
     *                                              *
     * visit GatewayAPI official docs here:         *
     * https://gatewayapi.com/docs/                 *
     *                                              *
     *----------------------------------------------*/

    public function sendMessage(array $recipients = [], string $message = "", string $sender = "element-notifier") {

        //Notify with an SMS using Gatewayapi.com
        $url = $this->url;
        $api_token = $this->apiToken;

        //Set SMS recipients and content
        $json = [

            'sender'        => $sender,
            'message'       => $message,
            'recipients'    => [],
        ];

        foreach ($recipients as $msisdn) {

            $json['recipients'][] = ['msisdn' => $msisdn];
        }

        //Make and execute the http request
        //Using the built-in 'curl' library
        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($ch,CURLOPT_USERPWD, $api_token.":");
        curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        $json = json_decode($result);

        return $json->ids;
    }
}