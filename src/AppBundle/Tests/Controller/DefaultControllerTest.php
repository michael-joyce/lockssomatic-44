<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


/**
 * Description of DefaultControllerTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DefaultControllerTest extends WebTestCase {

    public function testHomePage() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('LOCKSSOMatic', $crawler->filter('.page-header h1')->text());
    }
    
}
