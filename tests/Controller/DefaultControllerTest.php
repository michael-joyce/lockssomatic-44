<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use Nines\UserBundle\DataFixtures\UserFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of DefaultControllerTest.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DefaultControllerTest extends ControllerBaseCase {
    public function fixtures() : array {
        return [
            UserFixtures::class,
        ];
    }

    public function testAnonHomePage() : void {
        $crawler = $this->client->request('GET', '/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('LOCKSSOMatic', $crawler->filter('h1')->text());
    }

    public function testUserHomePage() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('LOCKSSOMatic', $crawler->filter('p')->text());
    }

    public function testAdminHomePage() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('LOCKSSOMatic', $crawler->filter('p')->text());
    }
}
