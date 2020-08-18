<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Lockss;

use App\Entity\Box;

/**
 * Class BoxStatusService.
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
