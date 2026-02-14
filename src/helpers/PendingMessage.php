<?php
namespace Element\Notifier\helpers;

use Element\Notifier\exceptions\{
    EmptyMessageException,
    InvalidPriorityException,
    InvalidSendtimeException,
    MissingRecipientsException
};

use Element\Notifier\providers\GatewayAPI;

class PendingMessage {

    private GatewayAPI $provider;
    private array $recipients;
    private string $message;
    private string $sender;
    private string $priority;
    private $callbackUrl = null;
    private $payload = null;

    public function __construct(GatewayAPI $provider, array $recipients, string $message, string $sender, string $priority = 'NORMAL') {

        if (empty($recipients)) {

            throw new MissingRecipientsException();
        }

        if ($message === '') {

            throw new EmptyMessageException();
        }

        $priority = strtoupper($priority);
        $allowed  = ['BULK', 'NORMAL', 'URGENT', 'VERY_URGENT'];

        if (!in_array($priority, $allowed, true)) {

            throw new InvalidPriorityException($priority, $allowed);
        }

        $this->provider   = $provider;
        $this->recipients = array_values($recipients);
        $this->message    = $message;
        $this->sender     = $sender;
        $this->priority   = strtoupper($priority);
    }

    /** Fluent setter for a webhook callback */

    public function with($callbackUrl = null, $payloadRawOrBase64 = null, $isBase64 = true) {

        if ($callbackUrl !== null) {

            if (!is_string($callbackUrl) || $callbackUrl === '' || !preg_match('#^https?://#i', $callbackUrl)) {

                throw new \InvalidArgumentException("callback_url must be a valid http(s) URL.");
            }

            $this->callbackUrl = $callbackUrl;
        }

        if ($payloadRawOrBase64 !== null) {

            if (!is_string($payloadRawOrBase64)) {

                throw new \InvalidArgumentException("payload must be a string.");
            }

            // Hvis udvikler giver rå binær/tekst, encoder vi den til Base64.
            $this->payload = $isBase64 ? $payloadRawOrBase64 : base64_encode($payloadRawOrBase64);
        }

        return $this;
    }


    /** Send now (without 'sendtime') */
    public function now() {

        if ($this->payload === null && $this->message === '') {

            throw new EmptyMessageException();
        }

        return $this->provider->dispatch(
            $this->recipients,
            $this->message,
            $this->sender,
            $this->priority,
            null,
            $this->callbackUrl,
            $this->payload
        );
    }

    /** Plan at a UNIX timestamp (seconds); mapped to the REST-feltet 'sendtime'. */
    public function schedule(int $timestamp) {

        if (!is_int($timestamp) || $timestamp <= time()) {

            throw new InvalidSendtimeException($timestamp);
        }

        if ($this->payload === null && $this->message === '') {

            throw new EmptyMessageException();
        }

        return $this->provider->dispatch(
            $this->recipients,
            $this->message,
            $this->sender,
            $this->priority,
            $timestamp,
            $this->callbackUrl,
            $this->payload
        );
    }
}