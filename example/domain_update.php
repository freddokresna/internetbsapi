<?php
include_once('../InternetBS.php');

/**
 * Let's see how we can perform different domain update commands
 * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/02_domain_create
 */
try {

    // Step 1. First of all let's create domain name
    $domainName = time().'-domain-update.com';
    $contact = array(
        'Firstname'    => 'John',
        'Lastname'     => 'Smith',
        'Organization' => 'My Company Name',
        'CountryCode'  => 'US',
        'State'        => 'WA',
        'City'         => 'Seattle',
        'Email'        => 'John.Smith@email.com',
        'Street'       => '100 Main str.',
        'Street2'      => 'h.43-125',
        'PostalCode'   => '98104',
        'PhoneNumber'  => '+1.9033337711',
    );

    $contacts = array(
        'Registrant' => $contact,
        'Admin'      => $contact,
        'Technical'  => $contact,
        'Billing'    => $contact,
    );

    $expirationDate = InternetBS::api()->domainCreate($domainName, $contacts);
    echo "Domain ".$domainName." registered, expiration date is ".date('Y-m-d', $expirationDate)."<br/>\n";

    // Step 2. Let's assign some NS for this domain
    InternetBS::api()->domainAssignNS($domainName, array('ns1.dns.org','ns2.dns.org'));


    // Step 3. Let's change domain password
    InternetBS::api()->domainUpdate($domainName, array(), array('transferAuthInfo' => '123456@qwerty'));

    // Step 4. Change registrant email
    $contacts = array(
        'Registrant' => array('Email' => 'new@email.com'),
    );
    InternetBS::api()->domainUpdate($contacts, $contacts);

    // Step 5. Finally let's see domain info for our test domain
    print_r(InternetBS::api()->domainInfo($domainName));


} catch (Exception $e) {
    echo "OOPS Error: ".$e->getMessage()."\n";
}
?>