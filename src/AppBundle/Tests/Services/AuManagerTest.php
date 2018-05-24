<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadDeposit;
use AppBundle\Entity\Au;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Plugin;
use AppBundle\Repository\AuRepository;
use AppBundle\Services\AuManager;
use ArrayObject;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of AuManagerTest
 *
 * @author michael
 */
class AuManagerTest extends BaseTestCase {

    /**
     * @var AuManager
     */
    private $manager;

    protected function setUp() {
        parent::setUp();
        $this->manager = $this->container->get(AuManager::class);
    }

    protected function getFixtures() {
        return [
            LoadDeposit::class
        ];
    }
//
//    public function testSanity() {
//        $this->assertInstanceOf(AuManager::class, $this->manager);
//    }
//
//    public function testAuSize() {
//        $this->assertEquals(600, $this->manager->auSize($this->getReference('au.1')));
//    }
//
//    public function testAuEmptySize() {
//        $this->assertEquals(0, $this->manager->auSize($this->getReference('au.2')));
//    }
//
//    public function testBuildAu() {
//        $plugin = $this->createMock(Plugin::class);
//        $provider = new ContentProvider();
//        $provider->setPlugin($plugin);
//
//        $deposit = $this->createMock(Deposit::class);
//        $deposit->method('getContentProvider')->will($this->returnValue($provider));
//        $au = $this->manager->buildAu($deposit, 'auid123');
//        $this->assertInstanceOf(Au::class, $au);
//        $this->assertEquals($provider, $au->getContentProvider());
//        $this->assertEquals($plugin, $au->getPlugin());
//        $this->assertEquals('auid123', $au->getAuid());
//    }
//
//
//    public function testFindOpenAuEmpty() {
//        $plugin = $this->createMock(Plugin::class);
//        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
//        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
//            'foo', 'bar', 'bax'
//        ]));
//        $plugin->method('getGeneratedParams')->will($this->returnValue([
//            'bar'
//        ]));
//        $provider = new ContentProvider();
//        $provider->setPlugin($plugin);
//        $deposit = $this->createMock(Deposit::class);
//        $deposit->method('getContentProvider')->will($this->returnValue($provider));
//        $deposit->method('getPlugin')->will($this->returnValue($plugin));
//        $deposit->method('getProperty')->will($this->returnValueMap([
//            ['foo', 'Some complex title'],
//            ['bar', 'other.property'],
//            ['bax', 'property the third!'],
//        ]));
//
//        $au = $this->manager->findOpenAu($deposit);
//        $this->assertInstanceOf(Au::class, $au);
//        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au->getAuid());
//    }
//
//    public function testFindOpenAuWithContent() {
//        $plugin = $this->createMock(Plugin::class);
//        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
//        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
//            'foo', 'bar', 'bax'
//        ]));
//        $plugin->method('getGeneratedParams')->will($this->returnValue([
//            'bar'
//        ]));
//        $provider = new ContentProvider();
//        $provider->setPlugin($plugin);
//
//        $deposit1 = $this->createMock(Deposit::class);
//        $deposit1->method('getContentProvider')->will($this->returnValue($provider));
//        $deposit1->method('getPlugin')->will($this->returnValue($plugin));
//        $deposit1->method('getProperty')->will($this->returnValueMap([
//            ['foo', 'Some complex title'],
//            ['bar', 'different.property'],
//            ['bax', 'property the third!'],
//        ]));
//
//        $au1 = $this->manager->findOpenAu($deposit1);
//        $this->assertInstanceOf(Au::class, $au1);
//        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au1->getAuid());
//
//        $deposit2 = $this->createMock(Deposit::class);
//        $deposit2->method('getContentProvider')->will($this->returnValue($provider));
//        $deposit2->method('getPlugin')->will($this->returnValue($plugin));
//        $deposit2->method('getProperty')->will($this->returnValueMap([
//            ['foo', 'Some complex title'],
//            ['bar', 'other.property'],
//            ['bax', 'property the third!'],
//        ]));
//
//        $au2 = $this->manager->findOpenAu($deposit2);
//        $this->assertInstanceOf(Au::class, $au1);
//        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au2->getAuid());
//
//        $this->assertEquals($au1, $au2);
//    }
//
//    public function testFindOpenAuContentDifferentAus() {
//        $plugin = $this->createMock(Plugin::class);
//        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
//        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
//            'foo', 'bar', 'bax'
//        ]));
//        $plugin->method('getGeneratedParams')->will($this->returnValue([
//            'bar'
//        ]));
//        $provider = new ContentProvider();
//        $provider->setPlugin($plugin);
//
//        $deposit1 = $this->createMock(Deposit::class);
//        $deposit1->method('getContentProvider')->will($this->returnValue($provider));
//        $deposit1->method('getPlugin')->will($this->returnValue($plugin));
//        $deposit1->method('getProperty')->will($this->returnValueMap([
//            ['foo', 'Some complex title'],
//            ['bar', 'different.property'],
//            ['bax', 'property the Billionth'],
//        ]));
//
//        $au1 = $this->manager->findOpenAu($deposit1);
//        $this->assertInstanceOf(Au::class, $au1);
//        $this->assertEquals('ca|example|plugin&bax~property+the+Billionth&foo~Some+complex+title', $au1->getAuid());
//
//        $deposit2 = $this->createMock(Deposit::class);
//        $deposit2->method('getContentProvider')->will($this->returnValue($provider));
//        $deposit2->method('getPlugin')->will($this->returnValue($plugin));
//        $deposit2->method('getProperty')->will($this->returnValueMap([
//            ['foo', 'Some complex title'],
//            ['bar', 'other.property'],
//            ['bax', 'property the third!'],
//        ]));
//
//        $au2 = $this->manager->findOpenAu($deposit2);
//        $this->assertInstanceOf(Au::class, $au1);
//        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au2->getAuid());
//
//        $this->assertNotEquals($au1, $au2);
//    }
//
//    public function testFindOpenAuSizeOverflow() {
//        $au = new Au();
//        $repo = $this->createMock(AuRepository::class);
//        $repo->method('getAuSize')->will($this->returnValue(500));
//        $repo->method('findOpenAu')->will($this->returnValue($au));
//        $this->manager->setAuRepository($repo);
//
//        $plugin = $this->createMock(Plugin::class);
//        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
//        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
//            'foo'
//        ]));
//        $plugin->method('getGeneratedParams')->will($this->returnValue([]));
//        $provider = new ContentProvider();
//        $provider->setPlugin($plugin);
//        $provider->setMaxAuSize(600);
//
//        $deposit = $this->createMock(Deposit::class);
//        $deposit->method('getSize')->willReturn(400);
//        $deposit->method('getContentProvider')->will($this->returnValue($provider));
//        $deposit->method('getPlugin')->will($this->returnValue($plugin));
//        $deposit->method('getProperty')->will($this->returnValueMap([
//            ['foo', 'bar'],
//        ]));
//
//        $foundAu = $this->manager->findOpenAu($deposit);
//        $this->assertFalse($au->isOpen());
//        $this->assertNotEquals($au, $foundAu);
//    }

    public function testValidateSingle() {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo'
        ]));
        $au->setPlugin($plugin);

        $deposit = new Deposit();
        $deposit->setProperty('foo', 'chicanery');

        $data = new ArrayObject();
        $data[] = [$deposit, 0];
        $iterator = $data->getIterator();
        $iterator->rewind();

        $repo = $this->createMock(AuRepository::class);
        $repo->method('iterateDeposits')->will($this->returnValue(
            $iterator
        ));
        $this->manager->setAuRepository($repo);

        $this->assertEquals(0, $this->manager->validate($au));
    }

    public function testValidate() {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo'
        ]));
        $au->setPlugin($plugin);

        $deposit = new Deposit();
        $deposit->setProperty('foo', 'chicanery');

        $data = new ArrayObject();
        $data[] = [$deposit, 0];
        $data[] = [$deposit, 0]; // same deposit.
        $iterator = $data->getIterator();
        $iterator->rewind();

        $repo = $this->createMock(AuRepository::class);
        $repo->method('iterateDeposits')->will($this->returnValue(
            $iterator
        ));
        $this->manager->setAuRepository($repo);

        $this->assertEquals(0, $this->manager->validate($au));
    }

    public function testValidateFail() {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo'
        ]));
        $au->setPlugin($plugin);

        $data = new ArrayObject();
        for($i = 0; $i < 10; $i++) {
            $deposit = new Deposit();
            $deposit->setProperty('foo', 'chicanery_' . $i);
            $data[] = [$deposit, 0];
        }
        $iterator = $data->getIterator();
        $iterator->rewind();

        $repo = $this->createMock(AuRepository::class);
        $repo->method('iterateDeposits')->will($this->returnValue(
            $iterator
        ));
        $this->manager->setAuRepository($repo);

        $this->assertEquals(9, $this->manager->validate($au));
    }



}
