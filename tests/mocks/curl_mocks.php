<?php

namespace Element\Notifier\providers;

use Element\Notifier\tests\GatewayAPIDeleteScheduledMessageTest as TestClass;

function curl_init() {

    return 'ch';
}

function curl_setopt($ch, $option, $value) {}
function curl_close($ch) {}
function curl_exec($ch) {

    return TestClass::$mockResult;
}

function curl_errno($ch) {

    return TestClass::$mockErrno;
}

function curl_error($ch) {

    return TestClass::$mockError;
}

function curl_getinfo($ch, $opt) {

    return TestClass::$mockStatus;
}