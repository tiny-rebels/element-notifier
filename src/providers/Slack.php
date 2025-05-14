<?php

namespace Element\Notifier\providers;

use Element\Notifier\Service;

use GuzzleHttp\{
    Exception\GuzzleException
};

class Slack extends Service {

    /*----------------------------------------------*
     *                                              *
     * visit Slack official docs here:              *
     * https://api.slack.com/messaging/webhooks     *
     *                                              *
     *----------------------------------------------*/

    public function sendMessage(array $recipients = [], string $message = "", string $sender = "element-notifier") {

        $url  = $this->url . $this->apiToken ;

        $data = (object) [

            "text" => $message
        ];

        try {

            $response = $this->httpClient->request('POST', $url, [

                'headers' => [

                    'Content-Type' => 'application/json',
                ],

                'json' => $data,

            ])->getBody();

        } catch (GuzzleException $exception) {

            return $exception;
        }

        return json_decode($response);
    }
}