<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Services;

use BeSimple\SoapClient\SoapClient;
use BeSimple\SoapCommon\Cache;
use Psr\Log\LoggerAwareTrait;

/**
 * Description of SoapClientBuilder
 *
 * @author michael
 */
class SoapClientBuilder {

    use LoggerAwareTrait;

    /**
     * Default options for SOAP clients.
     */
    const SOAP_OPTS = array(
        'soap_version' => SOAP_1_1,
        'cache_wsdl' => Cache::TYPE_NONE,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        'trace' => true,
        'exceptions' => true,
        'user_agent' => 'LOCKSSOMatic 1.0',
        'authentication' => SOAP_AUTHENTICATION_BASIC,
    );

    /**
     * @param string $wsdl
     * @param array $auth
     * @param array $soapOptions
     * @return SoapClient
     */
    public function build($wsdl, $auth, array $soapOptions = []) {
        $options = array_merge(self::SOAP_OPTS, $soapOptions, $auth);
        $client = @new SoapClient($wsdl, $options);
        return $client;
    }

}
