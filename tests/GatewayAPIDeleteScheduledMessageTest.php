<?php

namespace Element\Notifier\tests;

use GuzzleHttp\Client;

use Element\Notifier\providers\GatewayAPI;

use Element\Notifier\exceptions\{
    CurlErrorException,
    HttpException
};

use PHPUnit\Framework\TestCase;

class GatewayAPIDeleteScheduledMessageTest extends TestCase {

    public static $mockStatus = 204;
    public static $mockErrno  = 0;
    public static $mockError  = null;
    public static $mockResult = '';

    private function getProvider() {

        $httpClient = new Client();

        return new GatewayAPI(
            $httpClient,
            'https://gatewayapi.com/rest/mtsms',
            'FAKE_TOKEN'
        );
    }

    public function testDeleteScheduledSuccess() {

        self::$mockStatus = 204;
        self::$mockErrno  = 0;
        self::$mockError  = null;
        self::$mockResult = '';

        $provider = $this->getProvider();
        $this->assertTrue($provider->deleteScheduledMessage(12345));
    }

    public function testDeleteScheduledHttpError() {

        $this->expectException(HttpException::class);

        self::$mockStatus = 404;
        self::$mockErrno  = 0;
        self::$mockError  = null;
        self::$mockResult = '{"message":"Not found"}';

        $provider = $this->getProvider();
        $provider->deleteScheduledMessage(99999);
    }

    public function testDeleteScheduledCurlError() {

        $this->expectException(CurlErrorException::class);

        self::$mockStatus = 0;
        self::$mockErrno  = 28;
        self::$mockError  = "Operation timed out";
        self::$mockResult = false;

        $provider = $this->getProvider();
        $provider->deleteScheduledMessage(42);
    }

    public function testDeleteScheduledInvalidId() {

        $this->expectException(\InvalidArgumentException::class);

        $provider = $this->getProvider();
        $provider->deleteScheduledMessage(0);
    }
}