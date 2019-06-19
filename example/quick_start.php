<?php
include_once('../InternetBS.php');

/**
 * Simple example for API usage
 */

try {

    // You already can execute command at test server! Let's do:
    if(InternetBS::api()->domainCheck('check-at-test-server.com'))    {
        echo "Domain available!\n";
    } else {
        echo "Domain unavailable!\n";
    }

    // To execute command at live server you just need to set your API key and password.
    // Do it just once and after that all commands will be executed at live server.
    InternetBS::init('MyApiKey', 'mypassword');

    // Now you may execute command at live server
    if(InternetBS::api()->domainCheck('check-at-live-server.org'))    {
        echo "Domain available!\n";
    } else {
        echo "Domain unavailable!\n";
    }

    // Next API command will be also executed at live server
    // For example we may get current registrar prices
    print_r(InternetBS::api()->accountPriceListGet('USD'));


} catch (Exception $e) {
    echo "OOPS Error: ".$e->getMessage()."\n";
}




?>