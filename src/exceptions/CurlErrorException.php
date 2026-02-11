<?php

namespace Element\Notifier\exceptions;

class CurlErrorException extends NotifierException {

    protected int $curlErrno;
    protected ?string $curlError;

    public function __construct(int $curlErrno, ?string $curlError = null, string $message = '', int $code = 0, ?\Throwable $previous = null) {

        $this->curlErrno = $curlErrno;
        $this->curlError = $curlError;

        // Standardized message if it's not explicit stated
        if ($message === '') {

            $msg = "cURL error #{$curlErrno}";

            if ($curlError) {

                $msg .= ": {$curlError}";
            }

            $message = $msg;
        }

        parent::__construct($message, $code, $previous);
    }

    public function getCurlErrno(): int {

        return $this->curlErrno;
    }

    public function getCurlError(): ?string {

        return $this->curlError;
    }
}