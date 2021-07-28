<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Lockss;

use App\Entity\Au;
use App\Entity\Box;
use App\Entity\Deposit;
use App\Services\AuManager;
use Exception;
use Psr\Log\LoggerInterface;
use SoapFault;

class LockssService {
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AuManager
     */
    private $auManager;

    /**
     * @param string $name
     *
     * @throws SoapFault
     *
     * @return SoapClient
     *
     * @codeCoverageIgnore
     */
    protected function getClient(Box $box, $name) {
        $wsdl = "http://{$box->getHostname()}:{$box->getWebservicePort()}/ws/{$name}?wsdl";
        $options = [
            'login' => $box->getPln()->getUsername(),
            'password' => $box->getPln()->getPassword(),
        ];
        $client = new SoapClient($wsdl, $options);
        $client->setLogger($this->logger);

        return $client;
    }

    /**
     * @param string $method
     * @param array $parameters
     * @param mixed $serviceName
     *
     * @return mixed
     */
    public function call(Box $box, $method, $parameters = [], $serviceName = 'DaemonStatusService') {
        $client = $this->getClient($box, $serviceName);
        $response = $client->{$method}($parameters, $serviceName);

        if (isset($response->return)) {
            return $response->return;
        }

        return $response;
    }

    public function isDaemonReady(Box $box) {
        return $this->call($box, 'isDaemonReady');
    }

    public function platformStatus(Box $box) {
        return $this->call($box, 'getPlatformConfiguration');
    }

    public function boxStatus(Box $box) {
        return $this->call($box, 'queryRepositorySpaces', [
            'repositorySpaceQuery' => 'SELECT *',
        ]);
    }

    public function auStatus(Box $box, Au $au) {
        return $this->call($box, 'getAuStatus', [
            'auId' => $this->auManager->generateAuidFromAu($au, true),
        ]);
    }

    public function listAus(Box $box) {
        return $this->call($box, 'getAuIds');
    }

    public function listAuUrls(Box $box, Au $au) {
        return $this->call($box, 'getAuUrls', [
            'auId' => $this->auManager->generateAuidFromAu($au, true),
        ]);
    }

    public function isUrlCached(Box $box, Deposit $deposit) {
        return $this->call($box, 'isUrlCached', [
            'url' => $deposit->getUrl(),
            'auId' => $this->auManager->generateAuidFromDeposit($deposit, true),
        ], 'ContentService');
    }

    public function hash(Box $box, Deposit $deposit) {
        $params = [
            'hasherParams' => [
                'recordFilterStream' => true,
                'hashType' => 'V3File',
                'algorithm' => $deposit->getChecksumType(),
                'url' => $deposit->getUrl(),
                'auId' => $this->auManager->generateAuidFromDeposit($deposit, true),
            ],
        ];
        $response = $this->call($box, 'hash', $params, 'HasherService');
        if ( ! isset($response->blockFileDataHandler)) {
            $this->logger->error("Hash response for {$deposit->getUuid()} from {$box->getHostname()} request does not include blockFileDataHandler");

            throw new Exception($response->errorMessage);
        }
        $data = $response->blockFileDataHandler;
        $matches = [];
        if (preg_match('/^([[:xdigit:]]+)\\s+http:/m', $data, $matches)) {
            return $matches[1];
        }

        return '-';
    }

    /**
     * @required
     */
    public function setAuManager(AuManager $auManager) : void {
        $this->auManager = $auManager;
    }

    /**
     * @required
     */
    public function setLogger(LoggerInterface $soapLogger) : void {
        $this->logger = $soapLogger;
    }
}
