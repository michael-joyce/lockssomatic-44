<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services\Lockss;

use App\DataFixtures\AuFixtures;
use App\DataFixtures\BoxFixtures;
use App\DataFixtures\DepositFixtures;
use App\DataFixtures\PlnFixtures;
use App\Entity\Box;
use App\Entity\Deposit;
use App\Services\AuManager;
use App\Services\Lockss\ContentFetcher;
use App\Services\Lockss\LockssService;
use App\Services\Lockss\SoapClient;
use Exception;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;
use stdClass;

class ContentFetcherTest extends ControllerBaseCase {
    /**
     * @var ContentFetcher
     */
    private $fetcher;

    protected function fixtures() : array {
        return [
            DepositFixtures::class,
            BoxFixtures::class,
            AuFixtures::class,
            PlnFixtures::class,
            BoxFixtures::class,
        ];
    }

    protected function mockClient($method, $data) {
        $return = new stdClass();
        $return->return = $data;
        $client = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods([$method])
            ->getMock()
        ;
        $client->method($method)->willReturn($return);

        return $client;
    }

    public function testContainer() : void {
        $this->assertInstanceOf(ContentFetcher::class, $this->fetcher);
    }

    public function testFetchNoAgreement() : void {
        $this->expectExceptionMessage("Cannot download deposit when agreement 0.8 is less than 100%.");
        $deposit = $this->getReference('deposit.1');
        $deposit->setAgreement(0.8);
        $this->fetcher->fetch($deposit, 'lockss', 'lockss');
    }

    public function testFetchBox() : void {
        $root = vfsStream::setup();
        $file = vfsStream::newFile('tmp')->withContent('abc12345');

        $mock = $this->getMockBuilder(ContentFetcher::class)->onlyMethods(['download'])->getMock();
        $mock->expects($this->once())->method('download')->withAnyParameters()->willReturn($file);
        $logger = new NullLogger();
        $mock->setLogger($logger);

        /** @var Deposit $deposit */
        $deposit = $this->getReference('deposit.1');
        $deposit->setAgreement(1.0);
        $result = $mock->fetch($deposit, 'lockss', 'lockss');
        $this->assertNotNull($result);
    }

    protected function setup() : void {
        parent::setUp();
        $this->fetcher = self::$container->get(ContentFetcher::class);
    }
}
