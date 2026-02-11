<?php

namespace Element\Notifier\exceptions;

class InvalidSendtimeException extends NotifierException {

    /**
     * @var mixed
     */
    protected $providedValue;

    /**
     * @param mixed $providedValue
     */
    public function __construct($providedValue, $message = '', $code = 0, \Throwable $previous = null) {

        $this->providedValue = $providedValue;

        if ($message === '') {

            $message = "Invalid sendtime: '{$providedValue}'. It must be a UNIX timestamp in the future.";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getProvidedValue(){

        return $this->providedValue;
    }
}