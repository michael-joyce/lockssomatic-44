<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Lockss;

use App\Entity\Au;
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
     * @param AuManager $auManager
     * @required
     */
    public function setAuManager(AuManager $auManager) {
        $this->auManager = $auManager;
    }

    /**
     * @param LockssClient $client
     */
    public function setClient(LockssClient $client) : void {
        $this->client = $client;
    }

    /**
     * @param $method
     * @param $parameters
     *
     * @throws Exception
     *
     * @return mixed
     */
    protected function call($method, $parameters = []) {
        if ( ! $this->client) {
            throw new Exception('A LockssClient is required.');
        }
        $response = $this->client->call($method, $parameters);
        if (isset($response->return)) {
            return $response->return;
        }

        return $response;
    }

    public function boxStatus() {
        return $this->call('queryRepositorySpaces', [
            'repositorySpaceQuery' => 'SELECT *',
        ]);
    }

    public function listAus() {
        return $this->call('getAuIds');
    }

    public function auStatus(Au $au) {
        return $this->call('getAuStatus', [
            'auId' => $this->auManager->generateAuidFromAu($au, true),
        ]);
    }
}
