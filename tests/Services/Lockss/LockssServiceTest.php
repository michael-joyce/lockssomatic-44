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
use App\Services\AuManager;
use App\Services\Lockss\LockssService;
use App\Services\Lockss\SoapClient;
use Exception;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use Psr\Log\NullLogger;
use stdClass;

class LockssServiceTest extends ControllerBaseCase {
    /**
     * @var LockssService;
     */
    private $lockssService;

    protected function fixtures() : array {
        return [
            DepositFixtures::class,
            BoxFixtures::class,
            AuFixtures::class,
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
        $this->assertInstanceOf(LockssService::class, $this->lockssService);
    }

    public function testCall() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;

        $return = new stdClass();
        $return->return = ['data'];
        $client = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['testMethod'])
            ->getMock()
        ;
        $client->method('testMethod')->willReturn($return);
        $mock->method('getClient')->willReturn($client);

        $this->assertSame(['data'], $mock->call($this->getReference('box.1'), 'testMethod'));
    }

    public function testCallBadReturn() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;

        $return = new stdClass();
        $return->content = ['data'];
        $client = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['testMethod'])
            ->getMock()
        ;
        $client->method('testMethod')->willReturn($return);
        $mock->method('getClient')->willReturn($client);

        $this->assertEquals((object) ['content' => ['data']], $mock->call($this->getReference('box.1'), 'testMethod'));
    }

    public function testIsDaemonReady() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        $mock->method('getClient')->willReturn($this->mockClient('isDaemonReady', true));
        $this->assertTrue($mock->isDaemonReady($this->getReference('box.1')));
    }

    public function testBoxStatus() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        $response = [
            'activeCount' => 2,
            'deletedCount' => 0,
        ];
        $mock->method('getClient')->willReturn($this->mockClient('queryRepositorySpaces', $response));
        $result = $mock->boxStatus($this->getReference('box.1'));
        $this->assertSame(2, $result['activeCount']);
    }

    public function testQueryPolls() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        $response = [
            [
                'auid' => 'abc123',
                'auName' => 'WV Test',
                'pollStatus' => 'pending'
            ]
        ];
        $mock->method('getClient')->willReturn($this->mockClient('queryPolls', $response));
        $result = $mock->queryPolls($this->getReference('box.1'));
        $this->assertSame('abc123', $result[0]['auid']);
    }

    public function testQueryVotes() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        $response = [
            [
                'auid' => 'abc123',
                'auName' => 'WV Test',
                'pollStatus' => 'pending'
            ]
        ];
        $mock->method('getClient')->willReturn($this->mockClient('queryVotes', $response));
        $result = $mock->queryVotes($this->getReference('box.1'));
        $this->assertSame('abc123', $result[0]['auid']);
    }

    public function testPlatformStatus() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        $response = [
            'groups' => 'nogroup',
        ];
        $mock->method('getClient')->willReturn($this->mockClient('getPlatformConfiguration', $response));
        $result = $mock->platformStatus($this->getReference('box.1'));
        $this->assertSame('nogroup', $result['groups']);
    }

    public function testAuStatus() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        $response = [
            'accessType' => 'Subscription',
        ];
        $mock->method('getClient')->willReturn($this->mockClient('getAuStatus', $response));
        $auManager = $this->getMockBuilder(AuManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateAuidFromAu'])
            ->getMock()
        ;
        $auManager->method('generateAuidFromAu')->willReturn('abc123');

        $mock->setAuManager($auManager);
        $result = $mock->auStatus($this->getReference('box.1'), $this->getReference('au.1'));
        $this->assertSame('Subscription', $result['accessType']);
    }

    public function testListAus() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        $response = [
            (object) ['id' => 'abc123', 'name' => 'Test 1'],
            (object) ['id' => 'pdq456', 'name' => 'Test 2'],
        ];
        $mock->method('getClient')->willReturn($this->mockClient('getAuIds', $response));
        $result = $mock->listAus($this->getReference('box.1'));
        $this->assertCount(2, $result);
        $this->assertSame('pdq456', $response[1]->id);
    }

    public function testListAuUrls() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        $response = [
            'http://example.com',
            'http://example.org',
        ];
        $mock->method('getClient')->willReturn($this->mockClient('getAuUrls', $response));
        $auManager = $this->getMockBuilder(AuManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateAuidFromAu'])
            ->getMock()
        ;
        $auManager->method('generateAuidFromAu')->willReturn('abc123');

        $mock->setAuManager($auManager);
        $result = $mock->listAuUrls($this->getReference('box.1'), $this->getReference('au.1'));
        $this->assertCount(2, $result);
        $this->assertSame('http://example.com', $result[0]);
    }

    public function testIsUrlCached() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        $response = true;
        $mock->method('getClient')->willReturn($this->mockClient('isUrlCached', $response));

        $auManager = $this->getMockBuilder(AuManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateAuidFromDeposit'])
            ->getMock()
        ;
        $auManager->method('generateAuidFromDeposit')->willReturn('abc123');
        $mock->setAuManager($auManager);

        $result = $mock->isUrlCached($this->getReference('box.1'), $this->getReference('deposit.1'));
        $this->assertTrue($result);
    }

    public function testHash() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        $response = (object) [
            'blockFileDataHandler' => <<< 'ENDBFDH'
                # Block hashes from hilbert.local, 13:12:43 02/04/21
                # AU: LOCKSSOMatic AU 1 Deposit from OJS part 1
                # Hash algorithm: sha1
                # Encoding: Hex
                
                B9097EE74942D34E0F659159F46DEC10E43E81C3   http://localhost/A973E0D2EDED.zip
                # end
                ENDBFDH,
            'filesHashed' => 1,
        ];
        $mock->method('getClient')->willReturn($this->mockClient('hash', $response));

        $auManager = $this->getMockBuilder(AuManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateAuidFromDeposit'])
            ->getMock()
        ;
        $auManager->method('generateAuidFromDeposit')->willReturn('abc123');
        $mock->setAuManager($auManager);

        $result = $mock->hash($this->getReference('box.1'), $this->getReference('deposit.1'));
        $this->assertSame('B9097EE74942D34E0F659159F46DEC10E43E81C3', $result);
    }

    public function testHashUnknown() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        // This can happen if a box does nto know about the deposit yet.
        $response = (object) [
            'blockFileDataHandler' => <<< 'ENDBFDH'
                # Block hashes from hilbert.local, 13:12:43 02/04/21
                # AU: LOCKSSOMatic AU 1 Deposit from OJS part 1
                # Hash algorithm: sha1
                # Encoding: Hex
                # end
                ENDBFDH,
            'filesHashed' => 1,
        ];
        $mock->method('getClient')->willReturn($this->mockClient('hash', $response));

        $auManager = $this->getMockBuilder(AuManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateAuidFromDeposit'])
            ->getMock()
        ;
        $auManager->method('generateAuidFromDeposit')->willReturn('abc123');
        $mock->setAuManager($auManager);

        $result = $mock->hash($this->getReference('box.1'), $this->getReference('deposit.1'));
        $this->assertSame('-', $result);
    }

    public function testHashError() : void {
        $mock = $this->getMockBuilder(LockssService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock()
        ;
        $logger = new NullLogger();
        $mock->setLogger($logger);
        $response = (object) [
            'errorMessage' => 'an error',
            'filesHashed' => 1,
        ];
        $mock->method('getClient')->willReturn($this->mockClient('hash', $response));

        $auManager = $this->getMockBuilder(AuManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateAuidFromDeposit'])
            ->getMock()
        ;
        $auManager->method('generateAuidFromDeposit')->willReturn('abc123');
        $mock->setAuManager($auManager);

        try {
            $result = $mock->hash($this->getReference('box.1'), $this->getReference('deposit.1'));
        } catch (Exception $e) {
            $this->assertSame('an error', $e->getMessage());
        }
    }

    protected function setup() : void {
        parent::setUp();
        $this->lockssService = self::$container->get(LockssService::class);
    }
}
