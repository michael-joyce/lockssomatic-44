<?php


namespace App\Services\Lockss;

use App\Utilities\LockssClient;
use Exception;

class AbstractLockssService {

    /**
     * @var LockssClient
     */
    private $client;

    public function __construct() {
        $this->client = null;
    }

    public function setClient(LockssClient $client) {
        $this->client = $client;
    }

    /**
     * @param $method
     * @param $parameters
     *
     * @return mixed
     * @throws Exception
     */
    protected function call($method, $parameters) {
        if( ! $this->client) {
            throw new Exception("A LockssClient is required.");
        }
        $response = $this->client->call($method, $parameters);
        if( isset($response->return)) {
            return $response->return;
        }
        return $response;
    }

}
