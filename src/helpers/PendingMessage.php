<?php
namespace Element\Notifier\helpers;

use Element\Notifier\exceptions\{
    EmptyMessageException,
    InvalidPriorityException,
    MissingRecipientsException
};

use Element\Notifier\providers\GatewayAPI;

class PendingMessage {

    private GatewayAPI $provider;
    private array $recipients;
    private string $message;
    private string $sender;
    private string $priority;

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

    /** Send nu (uden 'sendtime') */
    public function now() {

        return $this->provider->dispatch(
            $this->recipients,
            $this->message,
            $this->sender,
            $this->priority,
            null,
        );
    }

    /** Planlæg til UNIX timestamp (sekunder); mappes til REST-feltet 'sendtime'. */
    public function schedule(int $timestamp){

        return $this->provider->dispatch(
            $this->recipients,
            $this->message,
            $this->sender,
            $this->priority,
            $timestamp
        );
    }
}