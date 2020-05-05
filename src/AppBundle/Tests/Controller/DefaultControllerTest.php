<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Tests\Controller;

use Nines\UserBundle\DataFixtures\ORM\LoadUser;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of DefaultControllerTest.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DefaultControllerTest extends BaseTestCase {
    public function getFixtures() {
        return [
            LoadUser::class,
        ];
    }

    public function testAnonHomePage() : void {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('LOCKSSOMatic', $crawler->filter('h1')->text());
    }

    public function testUserHomePage() : void {
        $client = $this->makeClient(LoadUser::USER);
        $crawler = $client->request('GET', '/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('LOCKSSOMatic', $crawler->filter('p')->text());
    }

    public function testAdminHomePage() : void {
        $client = $this->makeClient(LoadUser::ADMIN);
        $crawler = $client->request('GET', '/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('LOCKSSOMatic', $crawler->filter('p')->text());
    }
}
