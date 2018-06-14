<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use BeSimple\SoapClient\SoapClient;
use BeSimple\SoapCommon\Cache;

/**
 * Description of SoapClientBuilder
 */
class SoapClientBuilder {

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
