<?php

namespace Element\Notifier\exceptions;

class EmptyMessageException extends NotifierException {

    public function __construct(string $message = "The message cannot be empty.", int $code = 0, ?\Throwable $previous = null) {

        parent::__construct($message, $code, $previous);
    }
}