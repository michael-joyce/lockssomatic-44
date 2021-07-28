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
use App\Entity\Deposit;
use App\Repository\DepositRepository;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class DepositRepositoryTest extends ControllerBaseCase {
    /**
     * @var DepositRepository
     */
    private $repo;

    protected function fixtures() : array {
        return [
            PlnFixtures::class,
            DepositFixtures::class,
        ];
    }

    public function testContainer() : void {
        $this->assertInstanceOf(DepositRepository::class, $this->repo);
    }

    public function testSearchQuery() : void {
        $query = $this->repo->searchQuery('deposit');
        $this->assertStringContainsString('MATCH(e.uuid, e.url, e.title) AGAINST(:q BOOLEAN)', $query->getDQL());
    }

    public function testSearchQueryPln() : void {
        $query = $this->repo->searchQuery('deposit', $this->getReference('pln.1'));
        $this->assertStringContainsString('MATCH(e.uuid, e.url, e.title) AGAINST(:q BOOLEAN)', $query->getDQL());
        $this->assertStringContainsString('AND a.pln = :pln', $query->getDQL());
    }

    public function testCheckQuery() : void {
        $query = $this->repo->checkQuery();
        $this->assertStringContainsString('WHERE (d.agreement IS NULL or d.agreement < 1.0) AND (d.checked IS NULL OR d.checked < :yesterday)', $query->getDQL());
    }

    public function testCheckQueryPlns() : void {
        $query = $this->repo->checkQuery([$this->getReference('pln.1')]);
        $this->assertStringContainsString(' AND d.pln in :plns', $query->getDQL());
    }

    public function testCheckQueryUuids() : void {
        $query = $this->repo->checkQuery(null, ['uuid']);
        $this->assertStringContainsString('d.uuid in :uuids', $query->getDQL());
    }

    public function testCheckQueryAll() : void {
        $query = $this->repo->checkQuery(null, null, true);
        $this->assertStringNotContainsString('WHERE', $query->getDQL());
    }

    public function testCheckQueryCount() : void {
        $query = $this->repo->checkQuery(null, null, false, true);
        $this->assertStringContainsString('count(1)', $query->getDQL());
    }

    protected function setup() : void {
        parent::setUp();
        $this->repo = $this->entityManager->getRepository(Deposit::class);
    }
}
