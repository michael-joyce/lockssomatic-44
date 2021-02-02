<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use App\DataFixtures\BoxFixtures;
use App\Entity\Box;
use Nines\UserBundle\DataFixtures\UserFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class BoxControllerTest extends ControllerBaseCase {
    protected function fixtures() : array {
        return [
            UserFixtures::class,
            BoxFixtures::class,
        ];
    }

    public function testAnonIndex() : void {
        $crawler = $this->client->request('GET', '/pln/1/box/');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertSame(0, $crawler->selectLink('New')->count());
    }

    // pln 33 does not exist.
    public function testAnonIndex404() : void {
        $crawler = $this->client->request('GET', '/pln/33/box/');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserIndex() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/box/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(0, $crawler->selectLink('New')->count());
    }

    // pln 33 does not exist.
    public function testUserIndex404() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/33/box/');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminIndex() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/box/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(1, $crawler->selectLink('New')->count());
    }

    // pln 33 does not exist.
    public function testAdminIndex404() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/33/box/');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonShow() : void {
        $crawler = $this->client->request('GET', '/pln/1/box/1');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertSame(0, $crawler->selectLink('Edit')->count());
        $this->assertSame(0, $crawler->selectLink('Delete')->count());
    }

    // box 2 is not in pln 1.
    public function testAnonShowBox404() : void {
        $crawler = $this->client->request('GET', '/pln/1/box/2');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserShow() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/box/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(0, $crawler->selectLink('Edit')->count());
        $this->assertSame(0, $crawler->selectLink('Delete')->count());
    }

    // box 2 is not in pln 1.
    public function testUserShowBox404() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/box/2');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminShow() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/box/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(1, $crawler->selectLink('Edit')->count());
        $this->assertSame(1, $crawler->selectLink('Delete')->count());
    }

    // box 2 is not in pln 1.
    public function testAdminShowBox404() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/box/2');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonEdit() : void {
        $crawler = $this->client->request('GET', '/pln/1/box/1/edit');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserEdit() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/box/1/edit');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminEdit() : void {
        $this->login('user.admin');
        $formCrawler = $this->client->request('GET', '/pln/1/box/1/edit');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $form = $formCrawler->selectButton('Update')->form([
            'box[hostname]' => 'examplary.com',
            'box[ipAddress]' => '10.0.0.0',
            'box[protocol]' => 'TCP',
            'box[port]' => 8081,
            'box[webServiceProtocol]' => 'http',
            'box[sendNotifications]' => 0,
            'box[active]' => 1,
        ]);

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect('/pln/1/box/1'));
        $responseCrawler = $this->client->followRedirect();
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(2, $responseCrawler->filter('td:contains("examplary.com")')->count());
    }

    public function testAnonNew() : void {
        $crawler = $this->client->request('GET', '/pln/1/box/new');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserNew() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/box/new');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminNew() : void {
        $this->login('user.admin');
        $formCrawler = $this->client->request('GET', '/pln/1/box/new');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $form = $formCrawler->selectButton('Create')->form([
            'box[hostname]' => 'examplary.com',
            'box[ipAddress]' => '10.0.0.0',
            'box[protocol]' => 'TCP',
            'box[port]' => 8081,
            'box[webServiceProtocol]' => 'http',
            'box[sendNotifications]' => 0,
            'box[active]' => 1,
        ]);

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $responseCrawler = $this->client->followRedirect();
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(2, $responseCrawler->filter('td:contains("examplary.com")')->count());
    }

    public function testAnonDelete() : void {
        $crawler = $this->client->request('GET', '/pln/1/box/1/delete');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserDelete() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/box/1/delete');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminDelete() : void {
        $preCount = count($this->entityManager->getRepository(Box::class)->findAll());
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/box/1/delete');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $responseCrawler = $this->client->followRedirect();
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->entityManager->clear();
        $postCount = count($this->entityManager->getRepository(Box::class)->findAll());
        $this->assertSame($preCount - 1, $postCount);
    }
}
