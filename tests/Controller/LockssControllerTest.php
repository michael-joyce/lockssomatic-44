<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use App\DataFixtures\AuFixtures;
use App\DataFixtures\BoxFixtures;
use App\DataFixtures\ContentOwnerFixtures;
use App\DataFixtures\ContentProviderFixtures;
use App\DataFixtures\PlnFixtures;
use App\DataFixtures\PluginFixtures;
use App\Entity\Box;
use App\Entity\Pln;
use App\Services\FilePaths;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LockssControllerTest extends ControllerBaseCase {
    public function fixtures() : array {
        return [
            PlnFixtures::class,
            BoxFixtures::class,
            PluginFixtures::class,
            ContentOwnerFixtures::class,
            ContentProviderFixtures::class,
            AuFixtures::class,
        ];
    }

    public function testLockss() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getLockssXmlFile')->willReturn($url);

        static::$container->set('test.' . FilePaths::class, $fp);

        $this->client->request('GET', '/plnconfigs/1/properties/lockss.xml');
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/xml', $response->headers->get('Content-Type', null));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(strlen($data), $response->getFile()->getSize());
        $this->assertSame($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testLockss404() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        // file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getLockssXmlFile')->willReturn($url);

        static::$container->set('test.' . FilePaths::class, $fp);
        $this->client->request('GET', '/plnconfigs/1/properties/lockss.xml');
        $response = $this->client->getResponse();
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testLockssBadIp() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getLockssXmlFile')->willReturn($url);

        static::$container->set('test.' . FilePaths::class, $fp);
        $this->client->request(
            'GET',
            '/plnconfigs/1/properties/lockss.xml',
            [],
            [],
            [
                'REMOTE_ADDR' => '11.0.0.0',
            ]
        );
        $response = $this->client->getResponse();
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testLockssGoodIp() : void {
        $box = new Box();
        $box->setHostname('bananarama');
        $box->setIpAddress('10.0.0.12');
        $box->setProtocol('TCP');
        $box->setPort('1234');
        $box->setWebServicePort('11234');
        $box->setSendNotifications(false);
        $box->setActive(false);
        $box->setPln($this->entityManager->find(Pln::class, 1));
        $this->entityManager->persist($box);
        $this->entityManager->flush();

        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getLockssXmlFile')->willReturn($url);

        static::$container->set('test.' . FilePaths::class, $fp);
        $this->client->request('GET', '/plnconfigs/1/properties/lockss.xml', [
            'REMOTE_ADDR' => '10.0.0.12',
        ]);
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testTitleDb() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getTitleDbPath')->willReturn($url);

        static::$container->set('test.' . FilePaths::class, $fp);
        $this->client->request('GET', '/plnconfigs/1/titledbs/1/1/titledb_1.xml');
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/xml', $response->headers->get('Content-Type', null));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(strlen($data), $response->getFile()->getSize());
        $this->assertSame($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testTitleDb404() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        // file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getTitleDbPath')->willReturn($url);

        static::$container->set('test.' . FilePaths::class, $fp);
        $this->client->request('GET', '/plnconfigs/1/titledbs/1/1/titledb_1.xml');
        $response = $this->client->getResponse();
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testManifest() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getManifestPath')->willReturn($url);

        static::$container->set('test.' . FilePaths::class, $fp);
        $this->client->request('GET', '/plnconfigs/1/manifests/1/1/manifest_1.html');
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->headers->get('Content-Type', null));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(strlen($data), $response->getFile()->getSize());
        $this->assertSame($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testManifest404() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.xml');
        // file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getManifestPath')->willReturn($url);

        static::$container->set('test.' . FilePaths::class, $fp);
        $this->client->request('GET', '/plnconfigs/1/manifests/1/1/manifest_1.html');
        $response = $this->client->getResponse();
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testKeystore() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/lockss.keystore');
        file_put_contents($url, $data);

        $pln = $this->entityManager->find(Pln::class, 1);
        $pln->setKeystore($url); // anything works here.
        $this->entityManager->flush();

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getPluginsExportDir')->willReturn(dirname($url));

        static::$container->set('test.' . FilePaths::class, $fp);

        $this->client->request('GET', '/plnconfigs/1/plugins/lockssomatic.keystore');
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/x-java-keystore', $response->headers->get('Content-Type', null));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(strlen($data), $response->getFile()->getSize());
        $this->assertSame($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testKeystore404Null() : void {
        $pln = $this->entityManager->find(Pln::class, 1);
        $pln->setKeystore(null);
        $this->entityManager->flush();

        $this->client->request('GET', '/plnconfigs/1/plugins/lockssomatic.keystore');
        $response = $this->client->getResponse();
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testPluginList() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/dummy.html');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getPluginsManifestFile')->willReturn($url);

        static::$container->set('test.' . FilePaths::class, $fp);

        $this->client->request('GET', '/plnconfigs/1/plugins/index.html');
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->headers->get('Content-Type', null));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(strlen($data), $response->getFile()->getSize());
        $this->assertSame($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testPluginList404() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/dummy.html');
        // file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getPluginsManifestFile')->willReturn($url);

        static::$container->set('test.' . FilePaths::class, $fp);

        $this->client->request('GET', '/plnconfigs/1/plugins/index.html');
        $response = $this->client->getResponse();
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testPlugin() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/dummy.jar');
        file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getPluginsExportDir')->willReturn('vfs://root');

        static::$container->set('test.' . FilePaths::class, $fp);

        $this->client->request('GET', '/plnconfigs/1/plugins/dummy.jar');
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/java-archive', $response->headers->get('Content-Type', null));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(strlen($data), $response->getFile()->getSize());
        $this->assertSame($data, $response->getFile()->openFile()->fread(strlen($data)));
    }

    public function testPlugin404() : void {
        $data = '<root/>';

        $root = vfsStream::setUp();
        $url = vfsStream::url('root/dummy.jar');
        // file_put_contents($url, $data);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getPluginsExportDir')->willReturn('vfs://root');

        static::$container->set('test.' . FilePaths::class, $fp);

        $this->client->request('GET', '/plnconfigs/1/plugins/dummy.jar');
        $response = $this->client->getResponse();
        $this->assertSame(404, $response->getStatusCode());
    }

    protected function setup() : void {
        parent::setUp();
    }
}
