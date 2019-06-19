<?php
include_once('../InternetBS.php');

/**
 * Check if domain available for registration or not
 * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/01_domain_check
 */

try {

    // We can check if domain avaiable or not like this:
    if(InternetBS::api()->domainCheck('check-at-test-server.com'))    {
        echo "Domain available!\n";
    } else {
        echo "Domain unavailable!\n";
    }

    // Also you may wan to check some IDN domain name (just put domain name in Unicode)
    if(InternetBS::api()->domainCheck('москва.com'))    {
        echo "Domain available!\n";
    } else {
        echo "Domain unavailable!\n";
    }

    // ... or you may do same check and put domain name in ASCII form
    if(InternetBS::api()->domainCheck('xn--80adxhks.com'))    {
        echo "Domain available!\n";
    } else {
        echo "Domain unavailable!\n";
    }

} catch (Exception $e) {
    echo "OOPS Error: ".$e->getMessage()."\n";
}
?>