<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use App\DataFixtures\DepositFixtures;
use App\Repository\DepositRepository;
use Nines\UserBundle\DataFixtures\UserFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class DepositControllerTest extends ControllerBaseCase {
    protected function fixtures() : array {
        return [
            UserFixtures::class,
            DepositFixtures::class,
        ];
    }

    public function testAnonIndex() : void {
        $crawler = $this->client->request('GET', '/pln/1/deposit/');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserIndex() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/deposit/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminIndex() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/deposit/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonSearch() : void {
        $crawler = $this->client->request('GET', '/pln/1/deposit/search');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserSearch() : void {
        $repo = $this->createMock(DepositRepository::class);
        $repo->method('searchQuery')->willReturn([$this->getReference('deposit.1')]);
        $this->client->disableReboot();
        $this->client->getContainer()->set('test.'.DepositRepository::class, $repo);

        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/deposit/search');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Search')->form([
            'q' => 'deposit'
        ]);
        $responseCrawler = $this->client->submit($form);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminSearch() : void {
        $repo = $this->createMock(DepositRepository::class);
        $repo->method('searchQuery')->willReturn([$this->getReference('deposit.1')]);
        $this->client->disableReboot();
        $this->client->getContainer()->set('test.'.DepositRepository::class, $repo);

        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/deposit/search');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Search')->form([
            'q' => 'deposit'
        ]);
        $responseCrawler = $this->client->submit($form);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonShow() : void {
        $crawler = $this->client->request('GET', '/pln/1/deposit/1');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserShow() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/deposit/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testUserShowBadDeposit() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/2/deposit/1');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminShow() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/deposit/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
