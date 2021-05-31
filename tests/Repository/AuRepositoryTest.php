<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Repository;

use App\DataFixtures\AuFixtures;
use App\DataFixtures\DepositFixtures;
use App\Entity\Au;
use App\Entity\Deposit;
use App\Repository\AuRepository;
use Iterator;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class AuRepositoryTest extends ControllerBaseCase {
    /**
     * @var AuRepository
     */
    private $repo;

    protected function fixtures() : array {
        return [
            AuFixtures::class,
            DepositFixtures::class,
        ];
    }

    public function testContainer() : void {
        $this->assertInstanceOf(AuRepository::class, $this->repo);
    }

    public function testFindOpenAuNew() : void {
        $au = $this->repo->findOpenAu('not-an-au');
        $this->assertNull($au);
    }

    public function testFindOpenAu() : void {
        $au = $this->repo->findOpenAu('p~a');
        $this->assertNotNull($au);
    }

    public function testGetAuSize() : void {
        $au = $this->getReference('au.1');
        $this->assertSame(600, $this->repo->getAuSize($au));
    }

    public function testGetEmptyAuSize() : void {
        $au = $this->getReference('au.2');
        $this->assertSame(0, $this->repo->getAuSize($au));
    }

    public function testCountDeposits() : void {
        $au = $this->getReference('au.1');
        $this->assertSame(3, $this->repo->countDeposits($au));
    }

    public function testCountDepositsEmpty() : void {
        $au = $this->getReference('au.2');
        $this->assertSame(0, $this->repo->countDeposits($au));
    }

    public function testIterateDeposits() : void {
        $au = $this->getReference('au.1');
        $iterator = $this->repo->iterateDeposits($au);
        $this->assertInstanceOf(Iterator::class, $iterator);
        $this->assertTrue($iterator->valid());
        $this->assertInstanceOf(Deposit::class, $iterator->current());
        while ($iterator->current()) {
            $iterator->next();
        } // clean out the iterator or the db gets locked.
    }

    public function testIterateDepositsEmpty() : void {
        $au = $this->getReference('au.2');
        $iterator = $this->repo->iterateDeposits($au);
        $this->assertInstanceOf(Iterator::class, $iterator);
        $this->assertFalse($iterator->valid());
        $this->assertNull($iterator->current());
    }

    protected function setup() : void {
        parent::setUp();
        $this->repo = $this->entityManager->getRepository(Au::class);
    }
}
