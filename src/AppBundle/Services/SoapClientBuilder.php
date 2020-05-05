<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
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
    public const SOAP_OPTS = [
        'soap_version' => SOAP_1_1,
        'cache_wsdl' => Cache::TYPE_NONE,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        'trace' => true,
        'exceptions' => true,
        'user_agent' => 'LOCKSSOMatic 1.0',
        'authentication' => SOAP_AUTHENTICATION_BASIC,
    ];

    /**
     * Build and return a SOAP client.
     *
     * @param string $wsdl
     *
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
