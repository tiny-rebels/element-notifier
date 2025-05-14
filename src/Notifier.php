<?php

namespace Element\Notifier;

use Element\Notifier\providers\{
    GatewayAPI,
    Slack
};

use GuzzleHttp\Client as HttpClient;

/**
 * @method \Element\Notifier\Notifier connect(string $service = '', string $url = '', string $apiToken = '')
 */
class Notifier {

    /**
     * @param string $service
     * @param string $url
     * @param string $apiToken
     *
     * @return GatewayAPI|Slack|void
     */
    public static function connect(string $service = "", string $url = "", string $apiToken = "") {

        $httpClient = new HttpClient;

        switch ($service) {

            case 'gatewayAPI':

                return new GatewayAPI($httpClient, $url, $apiToken);

            case 'slack':

                return new Slack($httpClient, $url, $apiToken);

        }
    }
}