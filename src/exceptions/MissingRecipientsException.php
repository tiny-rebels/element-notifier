<?php

namespace Element\Notifier\exceptions;

class MissingRecipientsException extends NotifierException {

    public function __construct(string $message = "There HAS TO BE at least one recipient.", int $code = 0, ?\Throwable $previous = null) {

        parent::__construct($message, $code, $previous);
    }
}