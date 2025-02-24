<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use App\DataFixtures\AuFixtures;
use Nines\UserBundle\DataFixtures\UserFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class AuControllerTest extends ControllerBaseCase {
    protected function fixtures() : array {
        return [
            UserFixtures::class,
            AuFixtures::class,
        ];
    }

    public function testAnonIndex() : void {
        $crawler = $this->client->request('GET', '/pln/1/au/');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertSame(0, $crawler->selectLink('New')->count());
    }

    public function testUserIndex() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/au/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(0, $crawler->selectLink('New')->count());
    }

    public function testAdminIndex() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/au/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonShow() : void {
        $crawler = $this->client->request('GET', '/pln/1/au/1');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserShow() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/au/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminShow() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/au/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonDeposits() : void {
        $crawler = $this->client->request('GET', '/pln/1/au/1/deposits');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserDeposits() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/au/1/deposits');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminDeposits() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/au/1/deposits');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
