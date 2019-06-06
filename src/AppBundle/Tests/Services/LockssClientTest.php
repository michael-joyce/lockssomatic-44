<?php

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadAu;
use AppBundle\DataFixtures\ORM\LoadBox;
use AppBundle\DataFixtures\ORM\LoadDeposit;
use AppBundle\Services\LockssClient;
use AppBundle\Services\SoapClientBuilder;
use BeSimple\SoapClient\SoapClient;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class LockssClientTest extends BaseTestCase {

    /**
     * @var LockssClient
     */
    private $lockssClient;

    protected function getFixtures() {
        return [
            LoadBox::class,
            LoadAu::class,
            LoadDeposit::class,
        ];
    }

    protected function setup() : void {
        parent::setUp();
        $this->lockssClient = $this->getContainer()->get(LockssClient::class);
    }

    public function testSanity() {
        $this->assertInstanceOf(LockssClient::Class, $this->lockssClient);
    }

    public function testErrorHandler() {
        $this->lockssClient->errorHandler(1, "error string", __file__, __line__);
        $this->assertTrue($this->lockssClient->hasErrors());
        $errors = $this->lockssClient->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals("Error:error string", $errors[0]);
        $this->lockssClient->clearErrors();
        $this->assertFalse($this->lockssClient->hasErrors());
        $this->assertCount(0, $this->lockssClient->getErrors());
    }

    public function testExceptionHandler() {
        $exception = new Exception("hi hi", 23);
        $this->lockssClient->exceptionHandler($exception);

        $this->assertTrue($this->lockssClient->hasErrors());
        $errors = $this->lockssClient->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals("Exception:23:hi hi", $errors[0]);
        $this->lockssClient->clearErrors();
        $this->assertFalse($this->lockssClient->hasErrors());
        $this->assertCount(0, $this->lockssClient->getErrors());
    }

    public function testCallReturnString() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock();
        $mockClient->method('test')->willReturn((object) ['return' => 'cheddar']);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'test');
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
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'test');
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
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'test');
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
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'test');
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
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'test');
        $this->assertNull($response);
        $this->assertTrue($this->lockssClient->hasErrors());
        $this->assertEquals(['Exception:0:OOPS'], $this->lockssClient->getErrors());
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
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->isDaemonReady($box);
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
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'isDaemonReady');
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
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->lockssClient->getAuStatus($box, $au);
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
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->lockssClient->getAuStatus($box, $au);
        $this->assertNull($response);
        $this->assertFalse($this->lockssClient->hasErrors());
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
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->lockssClient->getAuUrls($box, $au);
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
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->lockssClient->getAuUrls($box, $au);
        $this->assertNull($response);
        $this->assertFalse($this->lockssClient->hasErrors());
    }

    public function testQueryRepositorySpaces() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'queryRepositorySpaces'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);
        $mockClient->method('queryRepositorySpaces')->willReturn((object) [
                'return' => [
                    (object) [
                        'active' => 2,
                        'size' => 123,
                    ],
                    (object) [
                        'active' => 1,
                        'size' => 1234
                    ],
                ]
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->lockssClient->queryRepositorySpaces($box, $au);
        $this->assertFalse($this->lockssClient->hasErrors());
        $this->assertCount(2, $response);
        $this->assertEquals([
            'active' => 2,
            'size' => 123,
            ], $response[0]);
        $this->assertEquals([
            'active' => 1,
            'size' => 1234
            ], $response[1]);
    }

    public function testQueryRepositorySpacesBoxDown() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'queryRepositorySpaces'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 0
        ]);
        $mockClient->method('queryRepositorySpaces')->will($this->throwException(new Exception("Shouldn't get here.")));

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->lockssClient->queryRepositorySpaces($box, $au);
        $this->assertNull($response);
        $this->assertFalse($this->lockssClient->hasErrors());
    }

    public function testIsUrlCached() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);
        $mockClient->method('isUrlCached')->willReturn((object)[
            'return' => 1
        ]);
        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->isUrlCached($box, $deposit);
        $this->assertEquals(true, $response);
    }

    public function testIsUrlCachedBoxDown() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 0
        ]);
        $mockClient->method('isUrlCached')->will($this->throwException(new Exception("Impossible.")));
        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->isUrlCached($box, $deposit);
        $this->assertNull($response);
    }

    public function testHash() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);
        $mockClient->method('isUrlCached')->willReturn((object)[
            'return' => 1
        ]);
        $mockClient->method('hash')->willReturn((object)$this->getHashData());

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->hash($box, $deposit);
        $this->assertEquals('601936759D11400112402DA98C24860D986DA8E5', $response);
    }

    public function testHashBadReturn() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);
        $mockClient->method('isUrlCached')->willReturn((object)[
            'return' => 1
        ]);
        $mockClient->method('hash')->willReturn((object) [
            'return' => (object) [
                'blockFileDataHandler' => '',
                'blockFileName' => 'foo.tmp',
                'bytesHashed' => 1234,
            ]
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->hash($box, $deposit);
        $this->assertNull($response);
    }

    public function testHashBoxDown() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 0
        ]);
        $mockClient->method('isUrlCached')->will($this->throwException(new Exception("Impossible.")));
        $mockClient->method('hash')->will($this->throwException(new Exception("Impossible.")));

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->hash($box, $deposit);
        $this->assertNull($response);
    }

    public function testHashNotCached() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);
        $mockClient->method('isUrlCached')->willReturn((object)[
            'return' => 0
        ]);
        $mockClient->method('hash')->will($this->throwException(new Exception("Impossible.")));

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->hash($box, $deposit);
        $this->assertNull($response);
    }

    public function testFetchFile() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);
        $mockClient->method('isUrlCached')->willReturn((object)[
            'return' => 1
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $mockHandler = new MockHandler([
            new Response(200, [], 'I am a cheese'),
        ]);
        $historyContainer = array();
        $history = Middleware::history($historyContainer);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($history);
        $httpClient = new Client(['handler' => $handlerStack]);
        $this->lockssClient->setHttpClient($httpClient);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->fetchFile($box, $deposit);
        $this->assertTrue(is_resource($response));
        $content = fread($response, 100);
        $this->assertEquals('I am a cheese', $content);

        $this->assertCount(1, $historyContainer);
        $request = $historyContainer[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertStringStartsWith('http://localhost:8080/ServeContent?url', (string)$request->getUri());
    }

    public function testFetchFileBoxDown() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 0
        ]);
        $mockClient->method('isUrlCached')->will($this->throwException(new Exception("Impossible.")));

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $mockHandler = new MockHandler([
            new Response(200, [], 'I am a cheese'),
        ]);
        $historyContainer = array();
        $history = Middleware::history($historyContainer);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($history);
        $httpClient = new Client(['handler' => $handlerStack]);
        $this->lockssClient->setHttpClient($httpClient);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->fetchFile($box, $deposit);
        $this->assertNull($response);

        $this->assertCount(0, $historyContainer);
    }

    public function testFetchFileNotCached() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);
        $mockClient->method('isUrlCached')->willReturn((object) [
                'return' => 0
        ]);
        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $mockHandler = new MockHandler([
            new Response(404, [], 'NOT FOUND'),
        ]);
        $historyContainer = array();
        $history = Middleware::history($historyContainer);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($history);
        $httpClient = new Client(['handler' => $handlerStack]);
        $this->lockssClient->setHttpClient($httpClient);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->fetchFile($box, $deposit);
        $this->assertNull($response);

        $this->assertCount(0, $historyContainer);
    }

    public function testFetchHttp500() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);
        $mockClient->method('isUrlCached')->willReturn((object) [
                'return' => 1
        ]);
        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $mockHandler = new MockHandler([
            new Response(500, [], 'Internal Error'),
        ]);
        $historyContainer = array();
        $history = Middleware::history($historyContainer);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($history);
        $httpClient = new Client(['handler' => $handlerStack]);
        $this->lockssClient->setHttpClient($httpClient);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->fetchFile($box, $deposit);
        $this->assertNull($response);

        $this->assertCount(1, $historyContainer);
        $this->assertTrue($this->lockssClient->hasErrors());
    }

    public function testFetchException() {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock();
        $mockClient->method('isDaemonReady')->willReturn((object) [
                'return' => 1
        ]);
        $mockClient->method('isUrlCached')->willReturn((object) [
                'return' => 1
        ]);
        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $mockHandler = new MockHandler([
            new Exception('Network error'),
        ]);
        $historyContainer = array();
        $history = Middleware::history($historyContainer);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($history);
        $httpClient = new Client(['handler' => $handlerStack]);
        $this->lockssClient->setHttpClient($httpClient);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->fetchFile($box, $deposit);
        $this->assertNull($response);

        $this->assertCount(1, $historyContainer);
        $this->assertTrue($this->lockssClient->hasErrors());
    }

    // This is a real example of what lockss returns here. sigh.
    private function getHashData() {
        $bfdh = <<<'ENDDATA'
# Block hashes from sophie.local, 15:46:21 06/14/18
# AU: LOCKSSOMatic AU 1 Deposit from OJS
# Hash algorithm: sha1
# Encoding: Hex

601936759D11400112402DA98C24860D986DA8E5   http://localhost/path.zip
# end

ENDDATA;
        return [
            'return' => (object) [
                'blockFileDataHandler' => $bfdh,
                'blockFileName' => 'foo.tmp',
                'bytesHashed' => 1234,
            ]
        ];
    }

}
