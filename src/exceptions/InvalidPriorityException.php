<?php
namespace Element\Notifier\exceptions;

class InvalidPriorityException extends NotifierException {

    public function __construct(string $priority, array $allowed = ['BULK', 'NORMAL', 'URGENT', 'VERY_URGENT'], int $code = 0, ?\Throwable $previous = null) {

        $message = sprintf("Invalid priority: %s. Allowed values: %s", $priority, implode(', ', $allowed));

        parent::__construct($message, $code, $previous);
    }
}