<?php

/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2018-06-06
 * Time: 4:54 PM
 */

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadAu;
use AppBundle\DataFixtures\ORM\LoadBox;
use AppBundle\Services\LockssClient;
use AppBundle\Services\SoapClientBuilder;
use BeSimple\SoapClient\SoapClient;
use Exception;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class LockssClientTest extends BaseTestCase {

    /**
     * @var LockssClient
     */
    private $client;

    protected function getFixtures() {
        return [
            LoadBox::class,
            LoadAu::class,
        ];
    }

    protected function setUp() {
        parent::setUp();
        $this->client = $this->getContainer()->get(LockssClient::class);
    }

    public function testSanity() {
        $this->assertInstanceOf(LockssClient::Class, $this->client);
    }

    public function testErrorHandler() {
        $this->client->errorHandler(1, "error string", __file__, __line__);
        $this->assertTrue($this->client->hasErrors());
        $errors = $this->client->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals("Error:error string", $errors[0]);
        $this->client->clearErrors();
        $this->assertFalse($this->client->hasErrors());
        $this->assertCount(0, $this->client->getErrors());
    }

    public function testExceptionHandler() {
        $exception = new Exception("hi hi", 23);
        $this->client->exceptionHandler($exception);

        $this->assertTrue($this->client->hasErrors());
        $errors = $this->client->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals("Exception:23:hi hi", $errors[0]);
        $this->client->clearErrors();
        $this->assertFalse($this->client->hasErrors());
        $this->assertCount(0, $this->client->getErrors());
    }

    public function testCallReturnString() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock();
        $mockClient->method('test')->willReturn((object) ['return' => 'cheddar']);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->client->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->client->call($box, 'path/to/service', 'test');
        $this->assertEquals('cheddar', $response);
    }

    public function testCallReturnNull() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock();
        $mockClient->method('test')->willReturn((object) ['return' => null]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->client->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->client->call($box, 'path/to/service', 'test');
        $this->assertNull($response);
    }

    public function testCallReturnArray() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock();
        $mockClient->method('test')->willReturn((object) ['return' => ['a', 'b']]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->client->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->client->call($box, 'path/to/service', 'test');
        $this->assertEquals(['a', 'b'], $response);
    }

    public function testCallReturnObject() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock();
        $mockClient->method('test')->willReturn((object) [
                'return' => (object) ['a' => 'b']
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->client->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->client->call($box, 'path/to/service', 'test');
        $this->assertEquals((object) ['a' => 'b'], $response);
    }

    public function testCallException() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock();
        $mockClient->method('test')->will($this->throwException(new Exception("OOPS")));

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->client->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->client->call($box, 'path/to/service', 'test');
        $this->assertNull($response);
        $this->assertTrue($this->client->hasErrors());
        $this->assertEquals(['Exception:0:OOPS'], $this->client->getErrors());
    }

    public function testIsDaemonReady() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->client->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->client->isDaemonReady($box);
        $this->assertEquals(true, $response);
    }

    public function testIsDaemonNotReady() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 0
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->client->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->client->call($box, 'path/to/service', 'isDaemonReady');
        $this->assertEquals(false, $response);
    }

    public function testGetAuStatus() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'getAuStatus'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);
        $mockClient->method('getAuStatus')->willReturn((object) [
                'return' => (object) [
                    'contentSize' => 123,
                    'publisher' => 'Publisher Inc.'
                ]
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->client->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->client->getAuStatus($box, $au);
        $this->assertEquals([
            'contentSize' => 123,
            'publisher' => 'Publisher Inc.'
            ], $response);
    }

    public function testGetAuStatusBoxDown() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'getAuStatus'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 0
        ]);
        $mockClient->method('getAuStatus')->will($this->throwException(new Exception("Shouldn't get here.")));

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->client->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->client->getAuStatus($box, $au);
        $this->assertNull($response);
        $this->assertFalse($this->client->hasErrors());
    }

    public function testGetAuUrls() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'getAuUrls'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);
        $mockClient->method('getAuUrls')->willReturn((object) [
                'return' => [
                    'http://example.com/manifest',
                    'http://example.com/path/to/one',
                ]
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->client->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->client->getAuUrls($box, $au);
        $this->assertEquals([
                    'http://example.com/manifest',
                    'http://example.com/path/to/one',
                ], $response);
    }

    public function testGetAuUrlsBoxDown() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'getAuUrls'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 0
        ]);
        $mockClient->method('getAuUrls')->will($this->throwException(new Exception("Shouldn't get here.")));

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->client->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->client->getAuUrls($box, $au);
        $this->assertNull($response);
        $this->assertFalse($this->client->hasErrors());
    }

}
