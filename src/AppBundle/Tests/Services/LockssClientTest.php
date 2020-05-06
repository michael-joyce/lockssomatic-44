<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

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
            ],
        ];
    }

    protected function getFixtures() {
        return [
            LoadBox::class,
            LoadAu::class,
            LoadDeposit::class,
        ];
    }

    public function testSanity() : void {
        $this->assertInstanceOf(LockssClient::class, $this->lockssClient);
    }

    public function testErrorHandler() : void {
        $this->lockssClient->errorHandler(1, 'error string', __FILE__, __LINE__);
        $this->assertTrue($this->lockssClient->hasErrors());
        $errors = $this->lockssClient->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Error:error string', $errors[0]);
        $this->lockssClient->clearErrors();
        $this->assertFalse($this->lockssClient->hasErrors());
        $this->assertCount(0, $this->lockssClient->getErrors());
    }

    public function testExceptionHandler() : void {
        $exception = new Exception('hi hi', 23);
        $this->lockssClient->exceptionHandler($exception);

        $this->assertTrue($this->lockssClient->hasErrors());
        $errors = $this->lockssClient->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Exception:23:hi hi', $errors[0]);
        $this->lockssClient->clearErrors();
        $this->assertFalse($this->lockssClient->hasErrors());
        $this->assertCount(0, $this->lockssClient->getErrors());
    }

    public function testCallReturnString() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock()
        ;
        $mockClient->method('test')->willReturn((object) ['return' => 'cheddar']);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'test');
        $this->assertSame('cheddar', $response);
    }

    public function testCallReturnNull() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock()
        ;
        $mockClient->method('test')->willReturn((object) ['return' => null]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'test');
        $this->assertNull($response);
    }

    public function testCallReturnArray() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock()
        ;
        $mockClient->method('test')->willReturn((object) ['return' => ['a', 'b']]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'test');
        $this->assertSame(['a', 'b'], $response);
    }

    public function testCallReturnObject() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock()
        ;
        $mockClient->method('test')->willReturn((object) [
            'return' => ['a' => 'b'],
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'test');
        $this->assertSame(['a' => 'b'], $response);
    }

    public function testCallException() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock()
        ;
        $mockClient->method('test')->will($this->throwException(new Exception('OOPS')));

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'test');
        $this->assertNull($response);
        $this->assertTrue($this->lockssClient->hasErrors());
        $this->assertSame(['Exception:0:OOPS'], $this->lockssClient->getErrors());
    }

    public function testIsDaemonReady() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->isDaemonReady($box);
        $this->assertSame(1, $response);
    }

    public function testIsDaemonNotReady() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 0,
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $response = $this->lockssClient->call($box, 'path/to/service', 'isDaemonReady');
        $this->assertSame(0, $response);
    }

    public function testGetAuStatus() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'getAuStatus'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('getAuStatus')->willReturn((object) [
            'return' => (object) [
                'contentSize' => 123,
                'publisher' => 'Publisher Inc.',
            ],
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->lockssClient->getAuStatus($box, $au);
        $this->assertSame([
            'contentSize' => 123,
            'publisher' => 'Publisher Inc.',
        ], $response);
    }

    public function testGetAuStatusBoxDown() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'getAuStatus'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 0,
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

    public function testGetAuUrls() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'getAuUrls'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('getAuUrls')->willReturn((object) [
            'return' => [
                'http://example.com/manifest',
                'http://example.com/path/to/one',
            ],
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->lockssClient->getAuUrls($box, $au);
        $this->assertSame([
            'http://example.com/manifest',
            'http://example.com/path/to/one',
        ], $response);
    }

    public function testGetAuUrlsBoxDown() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'getAuUrls'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 0,
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

    public function testQueryRepositorySpaces() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'queryRepositorySpaces'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('queryRepositorySpaces')->willReturn((object) [
            'return' => [
                (object) [
                    'active' => 2,
                    'size' => 123,
                ],
                (object) [
                    'active' => 1,
                    'size' => 1234,
                ],
            ],
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $au = $this->getReference('au.1');
        $response = $this->lockssClient->queryRepositorySpaces($box, $au);
        $this->assertFalse($this->lockssClient->hasErrors());
        $this->assertCount(2, $response);
        $this->assertSame([
            'active' => 2,
            'size' => 123,
        ], $response[0]);
        $this->assertSame([
            'active' => 1,
            'size' => 1234,
        ], $response[1]);
    }

    public function testQueryRepositorySpacesBoxDown() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'queryRepositorySpaces'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 0,
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

    public function testIsUrlCached() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('isUrlCached')->willReturn((object) [
            'return' => 1,
        ]);
        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->isUrlCached($box, $deposit);
        $this->assertSame(1, $response);
    }

    public function testIsUrlCachedBoxDown() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 0,
        ]);
        $mockClient->method('isUrlCached')->will($this->throwException(new Exception('Impossible.')));
        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->isUrlCached($box, $deposit);
        $this->assertFalse($response);
    }

    public function testHash() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('isUrlCached')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('hash')->willReturn((object) $this->getHashData());

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->hash($box, $deposit);
        $this->assertSame('601936759D11400112402DA98C24860D986DA8E5', $response);
    }

    public function testHashBadReturn() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('isUrlCached')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('hash')->willReturn((object) [
            'return' => (object) [
                'blockFileDataHandler' => '',
                'blockFileName' => 'foo.tmp',
                'bytesHashed' => 1234,
            ],
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->hash($box, $deposit);
        $this->assertNull($response);
    }

    public function testHashBoxDown() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 0,
        ]);
        $mockClient->method('isUrlCached')->will($this->throwException(new Exception('Impossible.')));
        $mockClient->method('hash')->will($this->throwException(new Exception('Impossible.')));

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->hash($box, $deposit);
        $this->assertNull($response);
    }

    public function testHashNotCached() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('isUrlCached')->willReturn((object) [
            'return' => 0,
        ]);
        $mockClient->method('hash')->will($this->throwException(new Exception('Impossible.')));

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $box = $this->getReference('box.1');
        $deposit = $this->getReference('deposit.1');
        $response = $this->lockssClient->hash($box, $deposit);
        $this->assertNull($response);
    }

    public function testFetchFile() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('isUrlCached')->willReturn((object) [
            'return' => 1,
        ]);

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $mockHandler = new MockHandler([
            new Response(200, [], 'I am a cheese'),
        ]);
        $historyContainer = [];
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
        $this->assertSame('I am a cheese', $content);

        $this->assertCount(1, $historyContainer);
        $request = $historyContainer[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringStartsWith('http://localhost:8080/ServeContent?url', (string) $request->getUri());
    }

    public function testFetchFileBoxDown() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 0,
        ]);
        $mockClient->method('isUrlCached')->will($this->throwException(new Exception('Impossible.')));

        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $mockHandler = new MockHandler([
            new Response(200, [], 'I am a cheese'),
        ]);
        $historyContainer = [];
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

    public function testFetchFileNotCached() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('isUrlCached')->willReturn((object) [
            'return' => 0,
        ]);
        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $mockHandler = new MockHandler([
            new Response(404, [], 'NOT FOUND'),
        ]);
        $historyContainer = [];
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

    public function testFetchHttp500() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('isUrlCached')->willReturn((object) [
            'return' => 1,
        ]);
        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $mockHandler = new MockHandler([
            new Response(500, [], 'Internal Error'),
        ]);
        $historyContainer = [];
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

    public function testFetchException() : void {
        $mockClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDaemonReady', 'isUrlCached', 'hash'])
            ->getMock()
        ;
        $mockClient->method('isDaemonReady')->willReturn((object) [
            'return' => 1,
        ]);
        $mockClient->method('isUrlCached')->willReturn((object) [
            'return' => 1,
        ]);
        $builder = $this->createMock(SoapClientBuilder::class);
        $builder->method('build')->willReturn($mockClient);
        $this->lockssClient->setSoapClientBuilder($builder);

        $mockHandler = new MockHandler([
            new Exception('Network error'),
        ]);
        $historyContainer = [];
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

    protected function setup() : void {
        parent::setUp();
        $this->lockssClient = $this->getContainer()->get(LockssClient::class);
    }
}
