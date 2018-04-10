<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Utilities;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use SoapClient;
use SoapFault;

/**
 * Description of LockssSoapClient
 */
class LockssSoapClient extends SoapClient {

    const DEFAULT_OPTS = array(
        'soap_version' => SOAP_1_1,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        'trace' => true,
        'exceptions' => true,
        'user_agent' => 'LOCKSSOMatic 1.0',
        'authentication' => SOAP_AUTHENTICATION_BASIC,
    );
    
    private $soapOptions;
    
    private $errors;

    public function __construct($wsdl, $options) {
        $this->errors = array();
        
        $this->soapOptions = array_merge(self::DEFAULT_OPTS, $options);
        set_error_handler(array($this, 'errorHandler'), E_ALL);
        set_exception_handler(array($this, 'exceptionHandler'));

        @parent::__construct($wsdl, $this->soapOptions);
        restore_error_handler();
        set_error_handler('var_dump', 0);
        @trigger_error('');

        restore_error_handler();
        restore_exception_handler();
    }

    public function errorHandler($errno, $errstr, $errfile, $errline) {
        $this->errors[] = implode(':', [$errfile, $errline, $errno, $errstr]);
        return true;
    }

    public function exceptionHandler(Exception $e) {
        $this->errors[] = implode(':', [$e->getFile(), $e->getLine(), $e->getCode(), $e->getMessage()]);
        return true;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function clearErrors() {
        $this->errors = array();
    }

    public function hasErrors() {
        return count($this->errors) > 0;
    }    
    
    public function __doRequest($request, $location, $action, $version, $one_way = 0) {
        $client = new Client([
            'headers' => [
                'User-Agent' => $this->soapOptions['user_agent'],
                'Content-Type' => 'application/xml',
                'Accept' => 'application/xml',
            ]
        ]);
        try {
            $response = $client->request('POST', $location, array(
                'body' => $request,
                'auth' => [$this->soapOptions['login'], $this->soapOptions['password']],
            ));
            dump($response->getHeader('Content-Type'));
//            dump($response);
//            dump((string)$response->getBody());
            return (string) $response->getBody();
        } catch (RequestException $e) {
            if($e->hasResponse()) {
                return (string)$e->getResponse()->getBody();
            }
            $code = "unknown";
            $message = $location . ' responded with ' . $e->getMessage();
            if ($e->hasResponse()) {
                $code = $e->getResponse()->getReasonPhrase();
            }
            throw new SoapFault($code, $message);
        }        
    }

}
