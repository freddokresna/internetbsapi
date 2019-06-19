<?php
include_once('../InternetBS.php');

/**
 * Example of create domain operation
 * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/02_domain_create list of parametes
 */
try {

    // Step 1. Prepare domain names for testing
    // We suppose that those names are avaiable for registration, in real code you will need to make sure about it first
    $domainName1 = time().'-intbstest-1.com';
    $domainName2 = time().'-intbstest-2.com';

    // Step 2. Prepare contact details for domain name registration
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

    // We will use same contact data for all 4 contact types
    $contacts = array(
        'Registrant' => $contact,
        'Admin'      => $contact,
        'Technical'  => $contact,
        'Billing'    => $contact,
    );


    // Step 3. Register new domain name for 3 year period and set NS using single command
    $expirationDate1 = InternetBS::api()->domainCreate($domainName1, $contacts, 3, array('Ns_list' => 'n1.mydns.org,n2.mydns.org'));
    echo "Domain ".$domainName1." registered, expiration date is ".date('Y-m-d', $expirationDate1)."<br/>\n";


    // Step 4. Let's register one more domain but this time we will just clone contact data from existing domain
    $expirationDate2 = InternetBS::api()->domainCreateCloneContactsFromDomain($domainName2, 1);
    echo "Domain ".$domainName2." registered, expiration date is ".date('Y-m-d', $expirationDate2)."<br/>\n";


} catch (Exception $e) {
    echo "OOPS Error: ".$e->getMessage()."\n";
}
?>