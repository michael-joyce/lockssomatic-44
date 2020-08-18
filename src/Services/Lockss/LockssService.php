<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Lockss;

use App\Entity\Box;
use App\Utilities\LockssClient;
use Exception;

class LockssService {
    /**
     * @var LockssClient
     */
    private $client;

    public function __construct() {
        $this->client = null;
    }

    /**
     * @param $method
     * @param $parameters
     *
     * @throws Exception
     *
     * @return mixed
     */
    protected function call($method, $parameters) {
        if ( ! $this->client) {
            throw new Exception('A LockssClient is required.');
        }
        $response = $this->client->call($method, $parameters);
        if (isset($response->return)) {
            return $response->return;
        }

        return $response;
    }

    public function setClient(LockssClient $client) : void {
        $this->client = $client;
    }

    public function boxStatus(Box $box) {
        return $this->call('queryRepositorySpaces', [
            'repositorySpaceQuery' => 'SELECT *',
        ]);
    }
}
