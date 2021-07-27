<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Repository;

use App\DataFixtures\DepositFixtures;
use App\DataFixtures\PlnFixtures;
use App\Entity\PluginProperty;
use App\Repository\PluginPropertyRepository;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class PluginPropertyRepositoryTest extends ControllerBaseCase {
    /**
     * @var PluginPropertyRepository
     */
    private $repo;

    protected function fixtures() : array {
        return [
            PlnFixtures::class,
            DepositFixtures::class,
        ];
    }

    public function testContainer() : void {
        $this->assertInstanceOf(PluginPropertyRepository::class, $this->repo);
    }

    protected function setup() : void {
        parent::setUp();
        $this->repo = $this->entityManager->getRepository(PluginProperty::class);
    }
}
