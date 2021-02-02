<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Lockss;

use App\Entity\Au;
use App\Entity\Deposit;
use App\Services\AuManager;
use App\Utilities\LockssClient;
use Exception;

class LockssService {
    /**
     * @var LockssClient
     */
    private $client;

    /**
     * @var AuManager
     */
    private $auManager;

    public function __construct() {
        $this->client = null;
    }

    /**
     * @param $method
     * @param $parameters
     * @param mixed $serviceName
     *
     * @throws Exception
     *
     * @return mixed
     */
    protected function call($method, $parameters = [], $serviceName = 'DaemonStatusService') {
        if ( ! $this->client) {
            throw new Exception('A LockssClient is required.');
        }
        $response = $this->client->call($method, $parameters, $serviceName);
        if (isset($response->return)) {
            return $response->return;
        }

        return $response;
    }

    /**
     * @required
     */
    public function setAuManager(AuManager $auManager) : void {
        $this->auManager = $auManager;
    }

    public function setClient(LockssClient $client) : void {
        $this->client = $client;
    }

    public function isDaemonReady() {
        $ready = $this->client->isDaemonReady();

        return $ready->return;
    }

    public function boxStatus() {
        return $this->call('queryRepositorySpaces', [
            'repositorySpaceQuery' => 'SELECT *',
        ]);
    }

    public function auStatus(Au $au) {
        return $this->call('getAuStatus', [
            'auId' => $this->auManager->generateAuidFromAu($au, true),
        ]);
    }

    public function listAus() {
        return $this->call('getAuIds');
    }

    public function listAuUrls(Au $au) {
        return $this->call('getAuUrls', [
            'auId' => $this->auManager->generateAuidFromAu($au, true),
        ]);
    }

    public function isUrlCached($deposit) {
        return $this->call('isUrlCached', [
            'url' => $deposit->getUrl(),
            'auId' => $this->auManager->generateAuidFromDeposit($deposit, true),
        ], 'ContentService');
    }

    public function hash(Deposit $deposit) {
        $params = [
            'hasherParams' => [
                'recordFilterStream' => true,
                'hashType' => 'V3File',
                'algorithm' => $deposit->getChecksumType(),
                'url' => $deposit->getUrl(),
                'auId' => $this->auManager->generateAuidFromDeposit($deposit, true),
            ],
        ];
        $response = $this->call('hash', $params, 'HasherService');
        if ( ! isset($response->blockFileDataHandler)) {
            throw new Exception($response->errorMessage);
        }
        $data = $response->blockFileDataHandler;
        $matches = [];
        preg_match('/^([[:xdigit:]]+)\\s+http:/m', $data, $matches);

        return $matches[1];
    }
}
