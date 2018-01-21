<?php

namespace AppBundle\Tests\Controller;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Nines\UserBundle\DataFixtures\ORM\LoadUser;

/**
 * Description of DefaultControllerTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DefaultControllerTest extends WebTestCase {

    public function setUp() {
        parent::setUp();
        $this->loadFixtures([
            LoadUser::class
        ]);
    }

    public function testAnonHomePage() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('LOCKSSOMatic', $crawler->filter('.page-header h1')->text());
    }

    public function testUserHomePage() {
        $client = $this->makeClient([
            'username' => 'user@example.com',
            'password' => 'secret',
        ]);
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('LOCKSSOMatic', $crawler->filter('.page-header h1')->text());
    }

    public function testAdminHomePage() {
        $client = $this->makeClient([
            'username' => 'admin@example.com',
            'password' => 'supersecret',
        ]);
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('LOCKSSOMatic', $crawler->filter('.page-header h1')->text());
    }

}
