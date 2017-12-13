<?php

namespace AppBundle\Tests\Controller;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Nines\UserBundle\Tests\DataFixtures\ORM\LoadUsers;


/**
 * Description of DefaultControllerTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DefaultControllerTest extends WebTestCase {

    public function setUp() {
        parent::setUp();
        $this->loadFixtures([
            LoadUsers::class
        ]);
    }
    
    public function testAnonHomePage() {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('LOCKSSOMatic', $crawler->filter('.page-header h1')->text());
    }
    
    public function testUserHomePage() {
        $client = $this->createClient([
            'user' => 'user@example.com',
            'pass' => 'secret',
        ]);
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('LOCKSSOMatic', $crawler->filter('.page-header h1')->text());        
    }
    
    public function testAdminHomePage() {
        $client = $this->createClient([
            'user' => 'admin@example.com',
            'pass' => 'supersecret',
        ]);
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('LOCKSSOMatic', $crawler->filter('.page-header h1')->text());        
    }
}
