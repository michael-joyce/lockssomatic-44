<?php


namespace App\Services\Lockss;

use App\Entity\Box;
use App\Utilities\LockssClient;
use Exception;

/**
 * Class BoxStatusService
 *
 * Checks the status of a box in the LOCKSS network.
 */
class BoxStatusService extends AbstractLockssService {

    public function check(Box $box) {
        return $this->call('queryRepositorySpaces', [
            'repositorySpaceQuery' => 'SELECT *',
        ]);
    }

}
