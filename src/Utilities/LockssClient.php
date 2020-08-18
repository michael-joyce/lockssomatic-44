<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Utilities;

use App\Entity\Box;
use Exception;
use Laminas\Soap\Client;

/**
 * Class LockssClient.
 *
 * @see https://docs.laminas.dev/laminas-soap/client/
 */
class LockssClient {
    /**
     * @var string
     */
    private $wsdl;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Box
     */
    private $box;

    private function __construct() {
    }

    /**
     * @param string $service
     *
     * @return LockssClient
     */
    public static function create(Box $box, $service = 'DaemonStatusService') {
        $wsdl = "http://{$box->getHostname()}:{$box->getWebservicePort()}/ws/{$service}?wsdl";
        $lockssClient = new LockssClient();
        $lockssClient->box = $box;
        $lockssClient->wsdl = $wsdl;
        $lockssClient->options = [
            'login' => $box->getPln()->getUsername(),
            'password' => $box->getPln()->getPassword(),
            'encoding' => 'utf-8',
            'soap_version' => SOAP_1_1,
        ];

        return $lockssClient;
    }

    public function getOption($name) {
        if (isset($this->options[$name])) {
            return $name;
        }
    }

    public function setOption($name, $value) : void {
        $this->options[$name] = $value;
    }

    public function call($method, $params = []) {
        $client = new Client($this->wsdl, $this->options);
        $readyResponse = $client->isDaemonReady();
        if (true !== $readyResponse->return) {
            throw new Exception("Daemon on {$this->box->getHostname()} reports not ready.");
        }

        return $client->{$method}($params);
    }
}
