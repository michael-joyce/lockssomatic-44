<?php

namespace AppBundle\Tests\Repository;

use AppBundle\DataFixtures\ORM\LoadAu;
use AppBundle\DataFixtures\ORM\LoadDeposit;
use AppBundle\Entity\Au;
use AppBundle\Entity\Deposit;
use AppBundle\Repository\AuRepository;
use Iterator;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class AuRepositoryTest extends BaseTestCase {

    /**
     * @var AuRepository
     */
    private $repo;

    protected function setUp() {
        parent::setUp();
        $this->repo = $this->em->getRepository(Au::class);
    }

    protected function getFixtures() {
        return array(
            LoadAu::class,
            LoadDeposit::class,
        );
    }

    public function testSanity() {
        $this->assertInstanceOf(AuRepository::class, $this->repo);
    }

    public function testFindOpenAuNew() {
        $au = $this->repo->findOpenAu('not-an-au');
        $this->assertNull($au);
    }

    public function testFindOpenAu() {
        $au = $this->repo->findOpenAu('p~a');
        $this->assertNotNull($au);
    }

    public function testGetAuSize() {
        $au = $this->getReference('au.1');
        $this->assertEquals(600, $this->repo->getAuSize($au));
    }

    public function testGetEmptyAuSize() {
        $au = $this->getReference('au.2');
        $this->assertEquals(0, $this->repo->getAuSize($au));
    }

    public function testCountDeposits() {
        $au = $this->getReference('au.1');
        $this->assertEquals(3, $this->repo->countDeposits($au));
    }

    public function testCountDepositsEmpty() {
        $au = $this->getReference('au.2');
        $this->assertEquals(0, $this->repo->countDeposits($au));
    }

    public function testIterateDeposits() {
        $au = $this->getReference('au.1');
        $iterator = $this->repo->iterateDeposits($au);
        $this->assertInstanceOf(Iterator::class, $iterator);
        $this->assertTrue($iterator->valid());
        $this->assertTrue(is_array($iterator->current()));
        $this->assertInstanceOf(Deposit::class, $iterator->current()[0]);
    }

    public function testIterateDepositsEmpty() {
        $au = $this->getReference('au.2');
        $iterator = $this->repo->iterateDeposits($au);
        $this->assertInstanceOf(Iterator::class, $iterator);
        $this->assertFalse($iterator->valid());
        $this->assertFalse($iterator->current());
    }
}
