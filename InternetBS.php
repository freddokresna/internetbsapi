<?php
/**
 * InternetBS class is designed to provide simple access to all InternetBS reseller API functions such as:
 *
 * - Domain related operations (Create, Update, Renew, Info, Check, Push and so on)
 * - Private Whois (Enable/Disable)
 * - Registrar lock
 * - Transfers and trades
 * - DNS management related operations
 * - Domain forwarding related operations
 * - Nameservers (host) related operations
 * - Account related operations
 *
 * This php class is FREE software. You can redistribute it and/or modify it,
 * use it in ANY commercial project without restrictions. Happy coding :)
 *
 * @author   Anton I. Grishan
 * @see      https://internetbs.net/?pId=apiclass
 * @website  https://github.com/GrishanAnton/InternetBS-API-PHP-Class
 * @version  1.2  30 Januar 2015
 * @license  LGPL
 * @license  http://opensource.org/licenses/LGPL-3.0 The GNU Lesser General Public License, version 3.0 (LGPL-3.0)
 */

// Try to load InternetBSApiInterface class definition if for some reason is not loaded yet
if(!class_exists('InternetBSApiCore', false))    {
    require_once('InternetBSApiCore.php');
}

class InternetBS extends InternetBSApiCore {

    /**
     * @var InternetBS object instance
     */
    private static $api = null;


    /**
     * Initiate API instance
     *
     * @param string $apiKey
     * @param string $password
     * @param null $agentName
     */
    public static function init($apiKey = null, $password = null, $agentName = null)   {

        // Check if we we have to use test api server
        if(empty($apiKey))    {
            $apiKey   = 'testapi';
            $password = 'testpass';
        }

        // Create object instance
        self::$api = new self($apiKey, $password, $agentName);
    }

    /**
     * Get access to API object instance
     * @return InternetBS
     */
    public static function api()    {

        // Check if we already initiate API access object instance
        if(empty(self::$api))    {
            // Ok. Let's initiate API to get access to test server
            self::init();
        }

        return self::$api;
    }





    /**
     * The command is intended to check whether a domain is available for registration or not
     *
     * @param string $domainName Domain name with extension (it can be regular or IDN domain name)
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/01_domain_check
     * @return boolean true if domain available for registration and false if not
     */
    public function domainCheck($domainName)   {
        $result = $this->domainCheckInDetails($domainName);
        return $this->_isSame($result['status'], 'AVAILABLE');
    }

    /**
     * Check if domain avaiable for registration and if it's
     * avaiable will return additional information about min/max
     * registration period and supported  features
     *
     * @param string $domainName
     * @return array contains information about domain
     */
    public function domainCheckInDetails($domainName)   {
        return $this->execute('Domain/Check', array('Domain' => $domainName));
    }


    /**
     * Assign NS list to some domain. NS list can be like:
     * array(
     * 'ns1.domain.com',
     * 'ns2.domain.org 23.23.11.43',
     * 'ns3.domain.org 11.11.11.11 22.22.22.22',
     * 'ns4.domain.org 2001:0DB8:AA10:0001:0000:0000:0000:00FB',
     * )
     *
     * @param string $domainName domain name
     * @param array $nsList list of ns
     * @return bool true if success executed
     *
     */
    public function domainAssignNS($domainName, array $nsList)   {
        return $this->domainUpdate($domainName, array(), array('Ns_list' => implode(',', $nsList)));
    }


    /**
     * Create domain name and close contacts from other domain
     *
     * @param string $domainName
     * @param string $cloneContactsFromDomain
     * @param int $period
     * @param array $other
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/02_domain_create
     *
     * @return int domain expiration date in UnixTime format
     */
    public function domainCreateCloneContactsFromDomain($domainName, $cloneContactsFromDomain, $period = 1, array $other = array())    {

        $params = array(
            'Domain' => $domainName,
            'CloneContactsFromDomain' => $cloneContactsFromDomain,
            'Period' => intval($period).'Y',
        );

        // Add other domain create params
        foreach($other as $key => $value)   {
            $params[$key] = $value;
        }

        $result = $this->execute('Domain/Create', $params);

        return strtotime($result['product'][0]['expiration']);
    }

    /**
     * The command is intended to register a new domain
     *
     * @param string $domainName
     * @param array  $contacts
     * @param int    $period
     * @param array  $other
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/02_domain_create
     *
     * @return int domain expiration date in UnixTime format
     */
    public function domainCreate($domainName, array $contacts, $period = 1, array $other = array())    {

        $params = array(
            'Domain' => $domainName,
            'Period' => intval($period).'Y',
        );


        foreach($contacts as $contactType => $values)   {
            foreach($values as $key => $value)   {
                $params[$contactType.'_'.$key] = $value;
            }
        }

        // Add other domain create params
        foreach($other as $key => $value)   {
            $params[$key] = $value;
        }

        $result = $this->execute('Domain/Create', $params);

        return strtotime($result['product'][0]['expiration']);
    }


    /**
     * The command is intended to update a domain, including Registrant Contact, Billing Contact, Admin Contact,
     * Tech. Contact, registrar locks status, epp auth info, name servers, private whois status, etc...
     *
     * @param string $domainName
     * @param array  $contacts
     * @param array  $other
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/03_domain_update
     *
     * @return bool true if domain success updated (or will be updated shortly)
     */
    public function domainUpdate($domainName, array $contacts, array $other = array())    {

        $params = array(
            'Domain' => $domainName,
        );


        foreach($contacts as $contactType => $values)   {
            foreach($values as $key => $value)   {
                $params[$contactType.'_'.$key] = $value;
            }
        }

        // Add other domain create params
        foreach($other as $key => $value)   {
            $params[$key] = $value;
        }

        return !$this->_isSame($this->execute('Domain/Update', $params, 'status'), 'FAILURE');
    }

    /**
     * The command is intended to renew a domain
     *
     * @param string  $domainName domain name to renew
     * @param integer $period the period for which the domain is renewed for
     * @param integer $currentExpirationDate current expiration date (or null) in format YYYY-mm-dd, ex.: 2018-04-27 (just to not renew same domain 2 times by mistake)
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/04_domain_renew
     * @return integer new expiration date
     */
    public function domainRenew($domainName, $period = 1, $currentExpirationDate = null)   {

        $params = array(
            'Domain' => $domainName,
            'Period' => intval($period).'Y',
        );

        // Add expiration date if any
        if(!empty($currentExpirationDate))    {

            $currentExpirationDate = trim($currentExpirationDate);

            if(preg_match('/^[0-9]+$/is', $currentExpirationDate))    {
                $params['currentexpiration'] = date('Y-m-d', intval($currentExpirationDate));
            } else if(preg_match('/^20[0-9]{2}-[01][0-9]-[0-3][0-9]$/is', $currentExpirationDate))    {
                $params['currentexpiration'] = $currentExpirationDate;
            } else {
                $this->_error(self::errorType_internal, 'Invalid current expiration value ['.$currentExpirationDate.'], expected format YYYY-mm-dd');
            }

        }

        // Execute renewal
        $result = $this->execute('Domain/Renew', $params);

        // Make sure that we have expected new expiration date
        if(isset($result['product']) && isset($result['product'][0]) && isset($result['product'][0]['newexpiration']))    {
            return strtotime($result['product'][0]['newexpiration']);
        } else {
            $this->_error(self::errorType_api, 'Can\'t obtain new expiration date from API response for '.$domainName.' domain');
        }
    }



    /**
     * The command is intended to return full details about a domain name; it includes contact details,
     * registrar lock status, private whois status, name servers and so on.
     *
     * @param string $domainName
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/05_domain_info
     * @return array contains all known information about registered domain
     */
    public function domainInfo($domainName)   {
        return $this->execute('Domain/Info', array('Domain' => $domainName));
    }


    /**
     * This command is intended to retrieve a list of domains in your account.
     *
     * @param $ExpiringOnly
     * @param $PendingTransferOnly
     * @param $rangeFrom
     * @param $rangeTo
     * @param $searchTermFilter
     * @param $CompactList
     * @param $SortBy
     * @param $extensionFilter
     *
     * @see  http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/06_domain_list
     *
     * @return array contains result
     */
    public function domainList($ExpiringOnly = null, $PendingTransferOnly = null, $rangeFrom = null, $rangeTo = null, $searchTermFilter = null, $CompactList = null, $SortBy = null, $extensionFilter = null)   {

        $params = array();

        if(!empty($ExpiringOnly))    {
            $params['ExpiringOnly'] = $ExpiringOnly;
        }

        if(!empty($PendingTransferOnly))    {
            $params['PendingTransferOnly'] = $PendingTransferOnly;
        }

        if(!empty($rangeFrom))    {
            $params['rangeFrom'] = $rangeFrom;
        }

        if(!empty($rangeTo))    {
            $params['rangeTo'] = $rangeTo;
        }

        if(!empty($searchTermFilter))    {
            $params['searchTermFilter'] = $searchTermFilter;
        }

        if(!empty($CompactList))    {
            $params['CompactList'] = $CompactList;
        }

        if(!empty($SortBy))    {
            $params['SortBy'] = $SortBy;
        }

        if(!empty($extensionFilter))    {
            $params['extensionFilter'] = $extensionFilter;
        }

        return $this->execute('Domain/List', $params, 'domain');
    }



    /**
     * The command is intended to move a domain from one internetbs.net user account to another.
     *
     * @param string $domainName Domain name with extension.
     * @param string $email Is the email account where the domain will be moved to.
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/08_domain_push
     * @return bool true if command success executed
     */
    public function domainPush($domainName, $email)   {
        return $this->_isSame($this->execute('Domain/Push', array('Domain' => $domainName, 'Destination' => $email), 'status'), 'SUCCESS');
    }

    /**
     * The command is intended to view a domain registry status.
     * To see domain status you need to be domain owner.
     *
     * @param $domainName
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/07_domain_registry_status
     * @return array statuses assigned to domain
     */
    public function domainRegistryStatus($domainName)  {
        return $this->execute('Domain/RegistryStatus', array('Domain' => $domainName), 'registrystatus');
    }


    /**
     * The command is intended to count total number of domains in the account.
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/01_domain_related/09_domain_count
     * @return array in format array("tld" => amount, ...)
     */
    public function domainCount()  {
        $counts = $this->execute('Domain/RegistryStatus', array());

        unset($counts['transactid']);
        unset($counts['status']);
        unset($counts['totaldomains']);

        return $counts;
    }

    /**
     * Set or unset domain registrar lock. If $isLocked not provided then method
     * will return current lock status for domain.
     *
     * @param string $domainName
     * @param bool $isLocked
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/03_registrar_lock/default
     * @return boolean current registrar lock status
     */
    public function domainRegistrarLock($domainName, $isLocked = null)   {

        // Do we need to change status or just return current one?
        if(null === $isLocked)    {
            // Just return current status
            return $this->_isSame($this->execute('Domain/RegistrarLock/Status', array('Domain' => $domainName), 'registrar_lock_status'), 'LOCKED');
        } else {
            // Ok. We need to set status
            $this->execute('Domain/RegistrarLock/'.($isLocked ? 'Enable' : 'Disable'), array('Domain' => $domainName));
            return (bool) $isLocked;
        }

    }

    /**
     * Enable/Disable and obtain current private whois status for domain.
     * If 2nd parameter omitted then method will just return current status.
     *
     * @param string $domainName
     * @param bool   $isEnabled
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/02_private_whois/default
     * @return bool true if private whois enabled and false if not
     */
    public function domainPrivateWhois($domainName, $isEnabled = null)   {

        // Do we need to change status or just return current one?
        if(null === $isEnabled)    {
            // Just return current status
            $pwhStatus = $this->execute('Domain/PrivateWhois/Status', array('Domain' => $domainName), 'privatewhoisstatus');
            return !empty($pwhStatus) && !$this->_isSame($pwhStatus, 'DISABLED');
        } else {
            // Ok. We need to set status
            $this->execute('Domain/PrivateWhois/'.($isEnabled ? 'Enable' : 'Disable'), array('Domain' => $domainName));
            return (bool) $isEnabled;
        }

    }


    /**
     * The command is intended to create a host also known as name server or child host.
     *
     * @param string $host The host to be created
     * @param array $ipList List of IP (IPv4 or IPv6) addresses separated by comma for the host. The list can be composed by just one or multiple IPs.
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/07_nameservers_related/01_domain_host_create
     * @return bool true if created
     */
    public function domainHostCreate($host, array $ipList)   {
        $params = array(
            'Host' => $host,
        );

        if(!empty($ipList))    {
            $params['IP_List'] = implode(',', $ipList);
        }

        return $this->_isSame($this->execute('Domain/Host/Create', $params, 'status'), 'SUCCESS');
    }


    /**
     * The command is intended to update a host; the command is replacing the current list of IP for the host with the
     * new one you provide. It is accepting the same parameters as domainHostCreate and will return the same results.
     *
     * @param string $host The host to be updated
     * @param array $ipList List of IP (IPv4 or IPv6) addresses separated by comma for the host. The list can be composed by just one or multiple IPs.
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/07_nameservers_related/02_domain_host_update
     *
     * @return bool true if updated
     */
    public function domainHostUpdate($host, array $ipList)   {
        $params = array(
            'Host' => $host,
        );

        if(!empty($ipList))    {
            $params['IP_List'] = implode(',', $ipList);
        }

        return $this->_isSame($this->execute('Domain/Host/Update', $params, 'status'), 'SUCCESS');
    }

    /**
     * The command is intended to retrieve existing host (name server) information for a specific host.
     *
     * @param string $host The host for which you want to retrieve information (you have to be owner of domain)
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/07_nameservers_related/03_domain_host_info
     *
     * @return array of IP assigned to host
     */
    public function domainHostInfo($host)   {
        return $this->execute('Domain/Host/Info', array('Host' => $host),'ip');
    }

    /**
     * The command is intended to delete (remove) an unwanted host. Note if your host is currently
     * used by one or more domains the operation will fail.
     *
     * @param string $host The host to be updated
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/07_nameservers_related/04_domain_host_delete
     *
     * @return bool true if success deleted
     */
    public function domainHostDelete($host)   {
        return $this->_isSame($this->execute('Domain/Host/Delete', array('Host' => $host), 'status'), 'SUCCESS');
    }

    /**
     * The command is intended to retrieve the list of hosts defined for a domain.
     *
     * @param string $domainName
     * @param bool $isCompact
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/07_nameservers_related/05_domain_host_list
     *
     * @return array information about existing hosts
     */
    public function domainHostList($domainName, $isCompact = true)   {
        return array_values($this->execute('Domain/Host/List', array('Domain' => $domainName, 'CompactList' => $isCompact ? 'yes' : 'no'), 'status'));
    }


    /**
     * The command is intended to add a new Email Forwarding rule.
     *
     * @param string $sourceEmail The URL Forwarding rule source. Ex. office@example.com (where example.com is the domain for which you setup email forwarding)
     * @param string $destinationEmail The forwarding rule destination. Ex. me@gmail.com,me@yahoo.com
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/06_forwarding_related/01_domain_email_forward_add
     *
     * @return bool true if success added
     */
    public function domainEmailForwardAdd($sourceEmail, $destinationEmail)   {
        return $this->_isSame($this->execute('Domain/EmailForward/Add', array('Source' => $sourceEmail, 'Destination' => $destinationEmail), 'status'), 'SUCCESS');
    }

    /**
     * The command is intended to remove an existing Email Forwarding rule.
     *
     * @param string $sourceEmail The email forwarding rule source, ex: office@example.com
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/06_forwarding_related/02_domain_email_forward_remove
     *
     * @return bool true if success deleted
     */
    public function domainEmailForwardRemove($sourceEmail)   {
        return $this->_isSame($this->execute('Domain/EmailForward/Remove', array('Source' => $sourceEmail), 'status'), 'SUCCESS');
    }

    /**
     * The command is intended to update an existing Email Forwarding rule.
     *
     * @param string $sourceEmail
     * @param string $destinationEmail
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/06_forwarding_related/03_domain_email_forward_update
     *
     * @return bool true if success updated
     */
    public function domainEmailForwardUpdate($sourceEmail, $destinationEmail)   {
        return $this->_isSame($this->execute('Domain/EmailForward/Update', array('Source' => $sourceEmail, 'Destination' => $destinationEmail), 'status'), 'SUCCESS');
    }


    /**
     * The command is intended to retrieve the list of email forwarding rules for a domain.
     *
     * @param string $domainName
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/06_forwarding_related/04_domain_email_forward_list
     *
     * @return array of existing email forwarding rules
     */
    public function domainEmailForwardList($domainName)   {
        $rules = $this->execute('Domain/EmailForward/List', array('Domain' => $domainName), 'rule');
        return is_array($rules) ? array_values($rules) : array();
    }


    /**
     * The command is intended to add a new URL Forwarding rule.
     *
     * @param string $sourceUrl
     * @param string $destinationUrl
     * @param bool $isFramed
     * @param string $siteTitle
     * @param string $metaDescription
     * @param string $metaKeywords
     * @param bool $redirect301
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/06_forwarding_related/05_domain_url_forward_add
     *
     * @return bool true if success added
     */
    public function domainUrlForwardAdd($sourceUrl, $destinationUrl, $isFramed = true, $siteTitle = null, $metaDescription = null, $metaKeywords = null, $redirect301 = null)   {

        $params = array(
            'Source'      => $sourceUrl,
            'Destination' => $destinationUrl,
            'isFramed'    => $isFramed ? 'YES' : 'NO',
        );

        if(!empty($siteTitle))    {
            $params['siteTitle'] = $siteTitle;
        }

        if(!empty($metaDescription))    {
            $params['metaDescription'] = $metaDescription;
        }

        if(!empty($metaKeywords))    {
            $params['metaKeywords'] = $metaKeywords;
        }

        if(!is_null($redirect301))    {
            $params['redirect301'] = $redirect301 ? 'YES' : 'NO';
        }

        return $this->_isSame($this->execute('Domain/UrlForward/Add', $params, 'status'), 'SUCCESS');
    }

    /**
     * The command is intended to update an existing URL Forwarding rule.
     *
     * @param string $sourceUrl
     * @param string $destinationUrl
     * @param bool   $isFramed
     * @param string $siteTitle
     * @param string $metaDescription
     * @param string $metaKeywords
     * @param bool   $redirect301
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/06_forwarding_related/07_domain_url_forward_update
     *
     * @return bool true if success updated
     */
    public function domainUrlForwardUpdate($sourceUrl, $destinationUrl, $isFramed = true, $siteTitle = null, $metaDescription = null, $metaKeywords = null, $redirect301 = null)   {

        $params = array(
            'Source'      => $sourceUrl,
            'Destination' => $destinationUrl,
            'isFramed'    => $isFramed ? 'YES' : 'NO',
        );

        if(!empty($siteTitle))    {
            $params['siteTitle'] = $siteTitle;
        }

        if(!empty($metaDescription))    {
            $params['metaDescription'] = $metaDescription;
        }

        if(!empty($metaKeywords))    {
            $params['metaKeywords'] = $metaKeywords;
        }

        if(!is_null($redirect301))    {
            $params['redirect301'] = $redirect301 ? 'YES' : 'NO';
        }

        return $this->_isSame($this->execute('Domain/UrlForward/Update', $params, 'status'), 'SUCCESS');
    }

    /**
     * The command is intended to remove an existing URL Forwarding rule.
     *
     * @param string $sourceUrl
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/06_forwarding_related/06_domain_url_forward_remove
     * @return bool true if success removed
     */
    public function domainUrlForwardRemove($sourceUrl)   {
        return $this->_isSame($this->execute('Domain/UrlForward/Remove', array('Source' => $sourceUrl), 'status'), 'SUCCESS');
    }

    /**
     * The command is intended to retrieve the list of URL forwarding rules for a domain.
     *
     * @param string $domainName
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/06_forwarding_related/08_domain_url_forward_list
     * @return array list of url forwarding rules
     */
    public function domainUrlForwardList($domainName)   {
        $rules = $this->execute('Domain/UrlForward/List', array('Domain' => $domainName), 'rule');
        return is_array($rules) ? array_values($rules) : array();
    }


    /**
     * The command is intended to retrieve the prepaid account balance for API key owner.
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/08_account_related/01_account_balance_get
     * @return array in format Currency => amount ex.: array('USD' => 450, 'GBP' => 800)
     */
    public function accountBalanceGet()   {

        $balance = $this->execute('Account/Balance/Get', array(), 'balance');

        $result = array();

        if(is_array($balance) && !empty($balance))    {
            foreach($balance as $info)   {
                $result[trim(strtoupper($info['currency']))] = floatval($info['amount']);
            }
        }

    return $result;
    }


    /**
     * The command is intended to set the default currency.
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/08_account_related/02_account_currency_get
     * @return string default currency code for API owner
     */
    public function accountDefaultCurrencyGet()   {
        return trim(strtoupper($this->execute('Account/DefaultCurrency/Get', array(), 'currency')));
    }

    /**
     * The command is intended to set the default currency. The default currency is used when you have available
     * balances in multiple currencies. In this case the prepaid funds in the default currency are used.
     *
     * @param string currency code
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/08_account_related/03_account_currency_set
     * @return boolean true if currency set
     */
    public function accountDefaultCurrencySet($currencyCode)   {
        return $this->_isSame($this->execute('Account/DefaultCurrency/Set', array('Currency' => $currencyCode), 'status'), 'SUCCESS');
    }


    /**
     * The command is intended to view the account (API owner) configuration.
     *
     * @param null $key
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/08_account_related/04_account_configuration_get
     * @return array or string (if key specified)
     */
    public function accountConfigurationGet($key = null)   {

        $result = $this->execute('Account/Configuration/Get', array(), $key);

        unset($result['transactid']);
        unset($result['status']);

        return $result;
    }

    /**
     * The command allows you to set the available configuration values for the API.
     *
     * @param $params
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/08_account_related/05_account_configuration_set
     * @return bool true if success
     */
    public function accountConfigurationSet($params)   {
        return $this->_isSame($this->execute('Account/Configuration/Set', $params, 'status'), 'SUCCESS');
    }


    /**
     * @param null $currencyCode
     * @param int $version
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/08_account_related/06_account_price_list_get
     * @return array with information about prices
     */
    public function accountPriceListGet($currencyCode = null, $version = 1)   {

        $params = array('version' => $version);

        if(!empty($currencyCode))    {
            $params['Currency'] = $currencyCode;
        }

        return $this->execute('Account/PriceList/Get', $params, 'product');
    }


    /**
     * The command is intended to cancel a pending incoming transfer request. If successful the corresponding
     * amount will be returned to your pre-paid balance.
     *
     * @param string $domainName
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/04_transfer_trade/02_domain_transfer_cancel
     * @return boolean true if success
     */
    public function domainTransferCancel($domainName)   {
        return $this->_isSame($this->execute('Domain/Transfer/Cancel', array('Domain' => $domainName), 'status'), 'SUCCESS');
    }

    /**
     * This command is intended to reattempt a transfer in case an error occurred because inaccurate transfer auth
     * info was provided or because the domain was locked or in some other cases where an intervention by the customer
     * is required before retrying the transfer.
     *
     * @param $domainName
     * @param null $authInfo
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/04_transfer_trade/07_domain_transfer_retry
     * @return boolean true if success
     */
    public function domainTransferRetry($domainName, $authInfo = null)   {

        $params = array('Domain' => $domainName);

        if(null !== $authInfo) {
            $params['transferAuthInfo'] = $authInfo;
        }

        return $this->_isSame($this->execute('Domain/Transfer/Retry', $params, 'status'), 'SUCCESS');
    }



    /**
     * The command is intended to retrieve the history of a transfer.
     *
     * @param string $domainName
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/04_transfer_trade/02_domain_transfer_cancel
     * @return boolean true if success
     */
    public function domainTransferHistory($domainName)   {
        $historyList = $this->execute('Domain/Transfer/History', array('Domain' => $domainName), 'history');
        return is_array($historyList) ? $historyList : array();
    }


    /**
     * The command is intended to reject a pending, outgoing transfer request (you are transferring away a domain).
     * The operation is possible only if there is a pending transfer away request from another Registrar. If you
     * do not reject the transfer within a specific time frame, in general 5 days for .com/.net domains, the transfer
     * will be automatically approved by the Registry.
     *
     * @param string $domainName
     * @param string $reason
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/04_transfer_trade/05_domain_transfer_away_reject
     * @return boolean true if success
     */
    public function domainTransferReject($domainName, $reason)   {
        return $this->_isSame($this->execute('Domain/TransferAway/Reject', array('Domain' => $domainName, 'Reason' => $reason), 'status'), 'SUCCESS');
    }

    /**
     * The command is intended to immediately approve a pending, outgoing transfer request (you are transferring a domain
     * away). The operation is possible only if there is a pending transfer away request from another Registrar. If you do
     * not approve the transfer within a specific time frame, in general 5 days for .com/.net domains, the transfer will
     * automatically be approved by the Registry.
     *
     * @param string $domainName
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/04_transfer_trade/04_domain_transfer_away_approve
     * @return boolean true if success
     */
    public function domainTransferApprove($domainName)   {
        return $this->_isSame($this->execute('Domain/TransferAway/Approve', array('Domain' => $domainName), 'status'), 'SUCCESS');
    }


    /**
     * The command is intended to resend the Initial Authorization for the Registrar Transfer email for a pending, incoming
     * transfer request. The operation is possible only if the current request has not yet been accepted/rejected by the
     * Registrant/Administrative contact, as it would make no sense to ask again.
     *
     * @param $domainName
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/04_transfer_trade/03_domain_transfer_resend_auth_email
     * @return boolean true if success
     */
    public function domainTransferResendAuthEmail($domainName)   {
        return $this->_isSame($this->execute('Domain/Transfer/ResendAuthEmail', array('Domain' => $domainName), 'status'), 'SUCCESS');
    }


    /**
     * The command is intended to initiate an incoming domain name transfer.
     *
     * @param $domainName
     * @param array $contacts
     * @param null $authInfo
     * @param array $other
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/api/04_transfer_trade/01_domain_transfer_initiate
     */
    public function domainTransfer($domainName, array $contacts, $authInfo = null, array $other = array())   {

        $params = array(
            'Domain' => $domainName,
        );

        foreach($contacts as $contactType => $values)   {
            foreach($values as $key => $value)   {
                $params[$contactType.'_'.$key] = $value;
            }
        }

        // Add other domain create params
        foreach($other as $key => $value)   {
            $params[$key] = $value;
        }

        if(!empty($authInfo))    {
            $params['transferAuthInfo'] = $authInfo;
        }

        $result = $this->execute('Domain/Transfer/Initiate', $params);

    return $this->_isSame($result['product'][0]['status'], 'SUCCESS');
    }

    /**
     * The command is used to initiate a trade. This operation supported by .fr/.re/.pm/.yt/.tf/.wf.
     *
     * @see http://www.afnic.fr/fr/votre-nom-de-domaine/choisir-mon-bureau-d-enregistrement/
     *
     * @param $domainName
     * @param array $contacts
     * @param null $authInfo
     * @param array $other
     *
     * @return bool true on success
     */
    public function domainTrade($domainName, array $contacts, $authInfo = null, array $other = array())   {
        $params = array(
            'Domain' => $domainName,
        );

        foreach($contacts as $contactType => $values)   {
            foreach($values as $key => $value)   {
                $params[$contactType.'_'.$key] = $value;
            }
        }

        // Add other domain create params
        foreach($other as $key => $value)   {
            $params[$key] = $value;
        }

        if(!empty($authInfo))    {
            $params['transferAuthInfo'] = $authInfo;
        }

        $result = $this->execute('Domain/Transfer/Trade', $params);

        return $this->_isSame($result['product'][0]['status'], 'SUCCESS');
    }


    /**
     * The command is intended to retrieve the list of DNS records for a specific domain
     * @param string $filterType (optional) You can specify here a DNS record type to retrieve only records of that type. By default all record are retrieved. Accepted values are: A, AAAA, CNAME, MX, TXT, NS and ALL
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/05_dns_management_related/04_domain_dns_record_list
     * @return array contains information about DNS record assigned to domain
     */
    public function domainDNSRecordList($domainName, $filterType = null)  {

        $params = array('Domain' => $domainName);

        if(!empty($filterType))    {
            $params['FilterType'] = $filterType;
        }

        return $this->execute('Domain/DnsRecord/List', $params, 'records');
    }


    /**
     * The command is intended to add a new DNS record to a specific zone (domain).
     *
     * @param $fullRecordName
     * @param $type Accepted values are: A, AAAA, DYNAMIC, CNAME, MX, SRV, TXT and NS
     * @param $value
     * @param null $ttl
     * @param null $priority
     * @param null $dynDnsLogin
     * @param null $dynDnsPassword
     * @param null $atRegistry
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/05_dns_management_related/01_domain_dns_record_add
     * @return boolean true if DNS record added
     */
    public function domainDNSRecordAdd($fullRecordName, $type, $value, $ttl = null, $priority = null, $dynDnsLogin = null, $dynDnsPassword = null, $atRegistry = null)  {

        $params = array(
            'FullRecordName' => $fullRecordName,
            'Type'           => $type,
            'Value'          => $value,
        );

        if(!is_null($ttl))    {
            $params['Ttl'] = $ttl;
        }

        if(!is_null($priority))    {
            $params['Priority'] = $priority;
        }

        if(!is_null($dynDnsLogin))    {
            $params['DynDnsLogin'] = $dynDnsLogin;
        }

        if(!is_null($dynDnsPassword))    {
            $params['DynDnsPassword'] = $dynDnsPassword;
        }

        if(!is_null($atRegistry))    {
            $params['atRegistry'] = $atRegistry;
        }

        return $this->_isSame($this->execute('Domain/DnsRecord/Add', $params, 'status'), 'SUCCESS');
    }


    /**
     * The command is intended to update an existing DNS record.
     *
     * @param $fullRecordName
     * @param $type Accepted values are: A, AAAA, DYNAMIC, CNAME, MX, SRV, TXT and NS
     * @param $value
     * @param null $currentValue
     * @param null $currentTtl
     * @param null $currentPriority
     * @param null $newValue
     * @param null $newTtl
     * @param null $newPriority
     * @param null $currentDynDnsLogin
     * @param null $currentDynDnsPassword
     * @param null $newDynDnsLogin
     * @param null $newDynDnsPassword
     * @param null $atRegistry
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/05_dns_management_related/03_domain_dns_record_update
     *
     * @return boolean true if DNS record added
     */
    public function domainDNSRecordUpdate($fullRecordName, $type, $value, $currentValue = null, $currentTtl = null, $currentPriority = null, $newValue = null, $newTtl = null, $newPriority = null, $currentDynDnsLogin = null, $currentDynDnsPassword = null, $newDynDnsLogin = null, $newDynDnsPassword = null, $atRegistry = null)    {

        $params = array(
            'FullRecordName' => $fullRecordName,
            'Type'           => $type,
            'Value'          => $value,
        );

        if(!is_null($currentValue))    {
            $params['CurrentValue'] = $currentValue;
        }
        if(!is_null($currentTtl))    {
            $params['CurrentTtl'] = $currentTtl;
        }
        if(!is_null($currentPriority))    {
            $params['CurrentPriority'] = $currentPriority;
        }
        if(!is_null($newValue))    {
            $params['NewValue'] = $newValue;
        }
        if(!is_null($newTtl))    {
            $params['NewTtl'] = $newTtl;
        }
        if(!is_null($newPriority))    {
            $params['NewPriority'] = $newPriority;
        }
        if(!is_null($currentDynDnsLogin))    {
            $params['CurrentDynDnsLogin'] = $currentDynDnsLogin;
        }
        if(!is_null($currentDynDnsPassword))    {
            $params['CurrentDynDnsPassword'] = $currentDynDnsPassword;
        }
        if(!is_null($newDynDnsLogin))    {
            $params['NewDynDnsLogin'] = $newDynDnsLogin;
        }
        if(!is_null($newDynDnsPassword))    {
            $params['NewDynDnsPassword'] = $newDynDnsPassword;
        }
        if(!is_null($atRegistry))    {
            $params['atRegistry'] = $atRegistry;
        }

        return $this->_isSame($this->execute('Domain/DnsRecord/Update', $params, 'status'), 'SUCCESS');
    }


    /**
     * The command is intended to remove a DNS record from a specific zone.
     *
     * @param $fullRecordName
     * @param $type
     * @param null $value
     * @param null $ttl
     * @param null $priority
     * @param null $dynDnsLogin
     * @param null $dynDnsPassword
     * @param null $atRegistry
     *
     * @see http://www.internetbs.net/ResellerRegistrarDomainNameAPI/api/05_dns_management_related/02_domain_dns_record_remove
     *
     * @return bool true if DNS record added
     */
    public function domainDNSRecordRemove($fullRecordName, $type, $value = null, $ttl = null, $priority = null, $dynDnsLogin = null, $dynDnsPassword = null, $atRegistry = null)  {
        $params = array(
            'FullRecordName' => $fullRecordName,
            'Type'           => $type,
        );

        if(!is_null($value))    {
            $params['Value'] = $value;
        }

        if(!is_null($ttl))    {
            $params['Ttl'] = $ttl;
        }

        if(!is_null($priority))    {
            $params['Priority'] = $priority;
        }

        if(!is_null($dynDnsLogin))    {
            $params['DynDnsLogin'] = $dynDnsLogin;
        }

        if(!is_null($dynDnsPassword))    {
            $params['DynDnsPassword'] = $dynDnsPassword;
        }

        if(!is_null($atRegistry))    {
            $params['atRegistry'] = $atRegistry;
        }

        return $this->_isSame($this->execute('Domain/DnsRecord/Remove', $params, 'status'), 'SUCCESS');

    }

    private $listOfSupportedTld = null;

    /**
     * Get list of TLD supported by InternetBS registrar
     *
     * @return array of tld like com,net,info,...
     */
    public function listOfSupportedTld() {

        // Do we already know list or we need to obtain it from registrar api?
        if(null === $this->listOfSupportedTld)    {
            $this->listOfSupportedTld = array();

            foreach($this->execute('Account/PriceList/Get', array('currency' => 'USD', 'version' => '3'), 'product') as $info)   {
                if($this->_isSame($info['type'], 'registration'))    {
                    $this->listOfSupportedTld[] = trim($info['name'], '.');
                }
            }
        }

    return $this->listOfSupportedTld;
    }


    /**
     * Execute API command name and check result of command execution
     * if issue detected we will throw exception.
     *
     * @param $commandName command name
     * @param array $params
     * @param string $key
     * @throw Exception in case of failure. Exception type depend on error reason
     */
    public function execute($commandName, array $params, $key = null)   {

        // Step 1. Execute command
        $result = $this->_executeApiCommand($commandName, $params);

        // Step 2. Check if command success executed
        if($this->_isSuccess())    {

            // Command success executed, so just return result
            if(null === $key)    {
                return $result;
            } else {
                return isset($result[$key]) ? $result[$key] : null;
            }

        } else {
            // Command execution failed, throw exception
            $exceptionClassName = $this->_getExceptionClassNameForErrorType($this->_lastErrorType());
            throw new $exceptionClassName($this->_lastErrorMessage(), $this->_lastErrorCode());
        }
    }
}