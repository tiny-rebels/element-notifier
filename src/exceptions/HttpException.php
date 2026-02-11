<?php

namespace Element\Notifier\exceptions;

use Throwable;

class HttpException extends NotifierException {

    protected int $statusCode;
    protected ?string $responseBody;

    public function __construct(int $statusCode, ?string $responseBody = null, ?string $message = null, ?int $code = 0, ?Throwable $previous = null) {

        $this->statusCode   = $statusCode;
        $this->responseBody = $responseBody;

        if ($message === '') {

            $message = "HTTP error {$statusCode}";

            if ($responseBody) {

                $message .= ": {$responseBody}";
            }
        }

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int {

        return $this->statusCode;
    }

    public function getResponseBody(): ?string {

        return $this->responseBody;
    }
}