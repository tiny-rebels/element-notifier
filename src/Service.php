<?php

namespace Element\Notifier;

use GuzzleHttp\Client as HttpClient;

abstract class Service {

    /**
     * Service dependencies
     */
    protected string $url;
    protected string $apiToken;
    protected HttpClient $httpClient;

    /**
     * Service constructor.
     * @param HttpClient $httpClient
     * @param string $url
     * @param string $apiToken
     */
    public function __construct(HttpClient $httpClient, string $url, string $apiToken) {

        $this->httpClient   = $httpClient;
        $this->url          = $url;
        $this->apiToken     = $apiToken;
    }

    /**
     * @param string $message
     * @param string $sender
     * @param array $recipients
     *
     * @return mixed
     */
    abstract public function sendMessage(array $recipients = [], string $message = "", string $sender = "");

    /**
     * @param $recipients
     * @param $message
     * @param $sender
     *
     * @return mixed
     */
    public function send($recipients, $message, $sender) {

        return $this->sendMessage($recipients, $message, $sender);
    }

}