<?php

/**
 * InternetBSApiCore class
 *
 * This class contains all the main functions to work with InternetBS.net API servers:
 * - send commands with parameters to live and test API server;
 * - parse API response in PHP array;
 * - handle information about errors;
 * - log API commands and responses.
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
class InternetBSApiCore {

    const apiClassVersion = '1.2'; // Interface version
    const errorType_api = 1; // Error happen during API command execution and registrar API server returned some error msg
    const errorType_network = 2; // Error happen when we try to send command to registrars API server
    const errorType_internal = 3; // Error happen due to issue with class usage logic
    // Config flag for logger
    const writeLogCase_never = 0; // Do not write any in log
    const writeLogCase_always = 1; // Write everything in log
    const writeLogCase_onError = 2; // Write only if error detected

    // API server credential

    /**
     * @var string $host API server host, defined automatically in class constructor
     */
    private $host = null;

    /**
     * @var string $host API key which is used to identify user at registrar server (you can obtain it from InternetBS control panel)
     */
    private $apiKey = null;

    /**
     * @var string $password password for API key, usually same as InternetBS account password, but we are recomended to use separated password ofr this (can be defined in InternetBS control panel)
     */
    private $password = null;

    /**
     * @var string $agentName HTTP agent name which will be sent to API server with command
     */
    private $agentName = null;

    /**
     * @var string if you have some discount provided to you by InternetBS you can set in here. Please use _setDiscountCode() method for this purpose.
     */
    private $discountCode = null;

    // Information about last detected error

    /**
     * @var integer contain code of last detected error type
     */
    private $lastErrorType = null;

    /**
     * @var integer error code
     */
    private $lastErrorCode = null;

    /**
     * @var string human readable error description
     */
    private $lastErrorMessage = null;

    /**
     * @var array array contains Exception class names per error type, ex.: array(self::errorType_network => 'MyOwnException'), if nothing defined then will be used standard exception class
     */
    private $exceptionClassNames = array(); // List of exception class names depend on error type
    // Log config related vars

    /**
     * @var integer code of case when we have to write information in log, by default - never
     */
    private $logCase = null;

    /**
     * @var array contains configuration for logger method
     */
    private $logConfig = array();

    /**
     * Constructor to create object instance
     *
     * @param string $apiKey    API key
     * @param string $password  password for your API key
     * @param string $agentName agent name which we may want to set when send some API command
     */
    public function __construct($apiKey, $password, $agentName = null) {

        // By default we will not write any log
        $this->_setLogConfig(self::writeLogCase_never);

        // Set host, api key and password
        $this->apiKey = $apiKey;
        $this->password = $password;

        // Check if we need to execute command at live or at test server  testapi.internet.bs normally use this domain
        $this->host = $this->_isSame($this->apiKey, 'testapi') ? '77.247.183.107' : 'api.internet.bs';

        $this->agentName = empty($agentName) ? get_class($this) . ' v.' . self::apiClassVersion : $agentName;
    }

    /**
     * If you set discount code with this method it will be used for each billable operation
     * @param string $discountCode discount code name
     */
    public function _setDiscountCode($discountCode) {
        $this->discountCode = $discountCode;
    }

    /**
     * Get currently defined discount code
     * @return string
     */
    public function _getDiscountCode() {
        return $this->discountCode;
    }

    /**
     * Set log config
     *
     * @param integer $logCase int code to know when we have to write log, never, always or only in case of some error
     * @param array   $logConfig array contains needed log config, if set to NULL then current config will not be changed
     */
    public function _setLogConfig($logCase, $logConfig = null) {

        // Set a log case
        if (in_array($logCase, array(self::writeLogCase_always, self::writeLogCase_never, self::writeLogCase_onError))) {
            $this->logCase = $logCase;
        } else {
            $this->_error(self::errorType_internal, 'Unknown value [' . $logCase . '] for $logCase');
        }

        // Update log config if need
        if (null !== $logConfig) {

            // Make sure that config provided as array
            if (is_array($logConfig)) {
                $this->logConfig = $logConfig;
            } else {
                $this->_error(self::errorType_internal, 'The $logConfig should be an array');
            }
        }
    }

    /**
     * Check if last command failed
     * @return boolean true if last API command call failed
     */
    public function _isFailed() {
        return !$this->_isSuccess();
    }

    /**
     * Check if last command success executed
     * @return boolean true if last API command call success processed
     */
    public function _isSuccess() {
        return null === $this->_lastErrorType();
    }

    /**
     * Get last error type
     * @return integer error type code
     */
    public function _lastErrorType() {
        return $this->lastErrorType;
    }

    /**
     * Get last error code
     * @return integer error code
     */
    public function _lastErrorCode() {
        return $this->lastErrorCode;
    }

    /**
     * Get last error description
     * @return string error description
     */
    public function _lastErrorMessage() {
        return $this->lastErrorMessage;
    }

    /**
     * Set exception class name per error type
     *
     * @param $errorType
     * @param $className
     */
    public function _setExceptionClassNameForErrorType($errorType, $className) {
        $this->exceptionClassNames[$errorType] = $className;
    }

    /**
     * Get exception class name for specific error type
     * @param $errorType
     * @return string class name
     */
    public function _getExceptionClassNameForErrorType($errorType) {
        return isset($this->exceptionClassNames[$errorType]) ? $this->exceptionClassNames[$errorType] : 'Exception';
    }

    /**
     * Execute command at InternetBS API server
     *
     * @param $commandName
     * @param array $params
     *
     * @see http://internetbs.net/ResellerRegistrarDomainNameAPI/?pId=apiclass
     *
     * @return array result of command execution
     */
    public final function _executeApiCommand($commandName, array $params) {

        // !!! PLEASE DO NOT CHANGE ANY IN THIS METHOD !!!

        $commandName = trim($commandName, ' /'); // Just to make sure that we have no leading space or slash in command name
        // Add discount code to command params if need
        $discountCode = $this->_getDiscountCode();

        if (!empty($discountCode)) {
            foreach (array('Account/PriceList/Get', 'Domain/Create', 'Domain/Renew', 'Domain/Transfer/Initiate', 'Domain/Trade') as $cmd) {
                if ($this->_isSame($commandName, $cmd)) {
                    $params['discountCode'] = $discountCode;
                    break;
                }
            }
        }

        // release information about last error
        $this->_releaseLastError();

        // added a mandatory params for API call
        $params['ApiKey'] = $this->apiKey;
        $params['Password'] = $this->password;

        $params['responseformat'] = 'JSON';

        // execute API command at server
        $apiRequestUrl = 'https://' . $this->host . '/' . $commandName;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiRequestUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->agentName);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $startTime = $this->_microtime(); // Write time when we start command execution
        $data = curl_exec($ch);
        $endTime = $this->_microtime(); // Write time when we got response
        // check if issue happen at network level
        if ($errorMsg = curl_error($ch)) {
            $this->_error(self::errorType_network, $errorMsg, curl_errno($ch));
        } else {
            // decode API response from JSON to php array
            $data = json_decode($data, true);

            // Check if we have error message at API level
            if (isset($data['status']) && $this->_isSame($data['status'], 'FAILURE')) {
                $this->_error(self::errorType_api, $data['message'], $data['code']);
            }
        }

        curl_close($ch);
        unset($ch);

        // Write command execution result in log if need
        $this->_writeLog($apiRequestUrl, $commandName, $params, $data, $startTime, $endTime);

        unset($apiRequestUrl);
        unset($commandName);
        unset($params);
        unset($startTime);
        unset($endTime);

        return $data;
    }

    /**
     * Write command and response to log
     *
     * @param string $url API url where we sent command
     * @param string $commandName API command name
     * @param array  $params array of params which we use to call command
     * @param array  $responses array response of API command (if any)
     * @param float  $startTime time when we sent command to API server
     * @param float  $endTime time when we got response from API server
     *
     * @return void nothing to return
     */
    protected function _writeLog($url, $commandName, $params, $responses, $startTime, $endTime) {

        // Check if we need to write some in log
        if (self::writeLogCase_always == $this->logCase || (self::writeLogCase_onError == $this->logCase && $this->_isFailed())) {

            // We have to write information in log, but now we need to understand which method we need to use.
            $logMethodName = '_writeLog_' . (isset($this->logConfig['method']) ? $this->logConfig['method'] : 'file');

            // Check if we have needed method
            if (method_exists($this, $logMethodName)) {

                // For security reason we may want to not save in log file API key, password and any other filed
                if (isset($this->logConfig['hideparam']) && is_array($this->logConfig['hideparam'])) {
                    foreach ($this->logConfig['hideparam'] as $key) {
                        if (isset($params[$key])) {
                            unset($params[$key]);
                        }
                    }
                }


                // Write information to log
                $this->$logMethodName($url, $commandName, $params, $responses, $startTime, $endTime);
            } else {
                $this->_error(self::errorType_internal, 'Unknown logger method "' . $logMethodName . '" in "' . get_class($this) . '"');
            }
        }
    }

    /**
     * Write log information into file
     *
     * @param string $url
     * @param string $commandName
     * @param array  $params
     * @param array  $responses
     * @param float  $startTime
     * @param float  $endTime
     */
    protected function _writeLog_file($url, $commandName, $params, $responses, $startTime, $endTime) {

        // Check if we know file path
        $path = isset($this->logConfig['path']) ? $this->logConfig['path'] : 'apiclass_' . date('Y_m_d') . '.log';

        if (file_put_contents($path, $this->_plainTextForLog($url, $commandName, $params, $responses, $startTime, $endTime) . "\n", FILE_APPEND) === false) {
            $this->_error(self::errorType_internal, 'Can\'t write entry log in "' . $path . '" file');
        }
    }

    /**
     * Write log information in stdout (just print it)
     *
     * @param string $url
     * @param string $commandName
     * @param array  $params
     * @param array  $responses
     * @param float  $startTime
     * @param float  $endTime
     */
    protected function _writeLog_screen($url, $commandName, $params, $responses, $startTime, $endTime) {
        echo $this->_plainTextForLog($url, $commandName, $params, $responses, $startTime, $endTime) . "\n";
    }

    /**
     * Build plain text message to write in log (file or just print or so)
     *
     * @param string $url
     * @param string $commandName
     * @param array  $params
     * @param array  $responses
     * @param float  $startTime
     * @param float  $endTime
     *
     * @return string contains log message
     */
    protected function _plainTextForLog($url, $commandName, $params, $responses, $startTime, $endTime) {

        $text = '';

        $text .= '### ' . date('Y-m-d H:i:s') . ' command "' . $commandName . '", result "' . ($this->_isSuccess() ? 'SUCCESS' : 'FAILED') . '", execution time: ' . round($endTime - $startTime, 3) . "s\n\n";
        $text .= $url . '?' . http_build_query($params) . "\n\n";
        $text .= "Command params: " . print_r($params, true) . "\n\n";
        $text .= "API response: " . print_r($responses, true) . "\n\n";

        $text .= "\n";

        return $text;
    }

    /**
     * Save information about detected error
     *
     * @param integer $type     error type
     * @param integer $code     error code
     * @param string  $message  error description
     *
     * @throws Exception if internal error detected (usually class usage logical error)
     */
    protected function _error($type, $message, $code = 0) {

        $this->lastErrorType = $type;
        $this->lastErrorCode = $code;
        $this->lastErrorMessage = $message;

        // In case of internal error we will throw an exception
        // because this sort of errors must be fixed during testing time
        if (self::errorType_internal == $type) {

            $exceptionClassName = $this->_getExceptionClassNameForErrorType($type);
            throw new $exceptionClassName($message, $code);
        }
    }

    /**
     * Release information about last detected error
     */
    protected function _releaseLastError() {
        $this->lastErrorType = null;
        $this->lastErrorCode = null;
        $this->lastErrorMessage = null;
    }

    protected function _microtime() {
        return function_exists('microtime') ? microtime(true) : time();
    }

    protected function _isSame($value1, $value2) {
        return trim(strtolower($value1)) == trim(strtolower($value2));
    }

}
