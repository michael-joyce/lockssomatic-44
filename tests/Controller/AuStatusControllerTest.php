<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use App\DataFixtures\AuStatusFixtures;
use Nines\UserBundle\DataFixtures\UserFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class AuStatusControllerTest extends ControllerBaseCase {
    protected function fixtures() : array {
        return [
            UserFixtures::class,
            AuStatusFixtures::class,
        ];
    }

    public function testAnonIndex() : void {
        $crawler = $this->client->request('GET', '/pln/1/au/1/status/');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserIndex() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/au/1/status/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testUserIndexBadAu() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/2/au/1/status/');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminIndex() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/au/1/status/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonShow() : void {
        $crawler = $this->client->request('GET', '/pln/1/au/1/status/1');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserShow() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/au/1/status/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testUserShowBadAu() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/au/2/status/1');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testUserShowBadStatus() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/2/au/1/status/2');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminShow() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/au/1/status/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
