<?php

namespace AppBundle\Tests\Controller;

use Nines\UserBundle\DataFixtures\ORM\LoadUser;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of DefaultControllerTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DefaultControllerTest extends BaseTestCase {

    public function getFixtures() {
        return [
            LoadUser::class
        ];
    }

    public function testAnonHomePage() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('LOCKSSOMatic', $crawler->filter('.page-header h1')->text());
    }

    public function testUserHomePage() {
        $client = $this->makeClient(LoadUser::USER);
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('LOCKSSOMatic', $crawler->filter('.page-header h1')->text());
    }

    public function testAdminHomePage() {
        $client = $this->makeClient(LoadUser::ADMIN);
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('LOCKSSOMatic', $crawler->filter('.page-header h1')->text());
    }

}
