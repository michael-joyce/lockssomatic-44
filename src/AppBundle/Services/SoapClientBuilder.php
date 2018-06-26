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
use Exception;

/**
 * Injectable and overrideable SOAP client builder.
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
     * Build and return a SOAP client.
     *
     * @param string $wsdl
     * @param array $auth
     * @param array $soapOptions
     * @return SoapClient
     */
    public function build($wsdl, array $auth, array $soapOptions = []) {
        $options = array_merge(self::SOAP_OPTS, $soapOptions, $auth);
        try {
            $client = @new SoapClient($wsdl, $options);
        } catch (Exception $e) {
            // Sigh. \SoapClient's internal error handling is so broken
            // that all of this is necessary to clear the errors so they don't
            // muck everything else up.
            set_error_handler('var_dump', 0);
            @trigger_error('');
            restore_error_handler();
            restore_exception_handler();
            throw $e;
        }
        return $client;
    }

}
