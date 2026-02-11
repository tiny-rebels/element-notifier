# Custom Helper Library that lets you send SMS or a notification easily

If you ever had the need to send an SMS or a notification, `Notifier` makes it easy for your with help of its helperclass `Notifier::connect()->sendMessage()`.

## Connect

To connect, you need to provide the helper class with the name of the service you want to use. For now it supports `gatewayAPI` and `slack` as the first parameter.

After that, you need to provide two extra parameters: the `url` and the `token`.

In PHP, this helper library uses a combination of [guzzlehttp/guzzle](https://docs.guzzlephp.org/en/stable/overview.html) and [cURL](http://php.net/manual/en/book.curl.php) library under the hood to make HTTP requests.

Example on how to use it?

```php
<?php

namespace MyApp\awesome-project;

use Element\Notifier\Notifier;

use Noodlehaus\Config;

use Symfony\Contracts\EventDispatcher\Event;

/*
 * Real example from a project of mine...
 */
class SMS_DownNotification {

    protected Config $config;

    /**
     * SMS_DownNotification constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config) {

        $this->config = $config;
    }

    /**
     * @param Event $event
     */
    public function handle(Event $event) {

        $url   = $this->config->get('service.ss.url');
        $token = $this->config->get('service.ss.apiToken');

        // Please note! In order to send an url in the message, then the link first has to be whitelisted by gatewayAPI ☝️
        // visit GatewayAPI docs for more information : 
        $message = "An endpoint is DOWN with status code of {$event->endpoint->status->status_code}";

        $send = Notifier::connect("gatewayAPI", $url, $token)->sendMessage([4511223344, 4555667788], $message, "kør'kort");
        
        $send->now();                       // <- notice the now() function at the end! This will send the message straight away...
        $send->schedule(int $timestamp);    // <- notice the schedule() function at the end! This will send the message at the scheduled time...
    }
}
```
