<?php
include_once('../InternetBS.php');

/**
 * Renew domain name
 * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/04_domain_renew
 */

try {

    // NOTE: DOMAIN MUST EXIST AND YOU HAVE TO BE OWNER OF THIS DOMAIN NAME.
    // Otherwise operation will failed.

    $domainName = 'example.com'; // Set here domain name for which you wan to perform test

    // Renew domain for 1 year, without checking current expiration date
    $newExpirationDate = InternetBS::api()->domainRenew($domainName);

    // Ok Now let's renew domain for 1 years, and provide current expiration date in UnixTime format
    $newExpirationDate = InternetBS::api()->domainRenew($domainName, 1, $newExpirationDate);

    // Let's renew it again for 2 years but this time we will set expiration date in string like YYYY-MM-DD
    $newExpirationDate = InternetBS::api()->domainRenew($domainName, 2, date('Y-m-d', $newExpirationDate));

    try {

        InternetBS::api()->domainRenew($domainName, 1, '1997-02-11');

    } catch(Exception $e){
        echo "Renewal failed but this was expected because current expiration date were set wrongly.\n".$e->getMessage()." ".$e->getCode()."\n";
    }


} catch (Exception $e) {
    echo "OOPS Error: ".$e->getMessage()."\n";
}
?>