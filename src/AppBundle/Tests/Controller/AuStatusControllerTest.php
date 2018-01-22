<?php

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\ORM\LoadAuStatus;
use AppBundle\Entity\AuStatus;
use Nines\UserBundle\DataFixtures\ORM\LoadUser;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class AuStatusControllerTest extends BaseTestCase {

    protected function getFixtures() {
        return [
            LoadUser::class,
            LoadAuStatus::class
        ];
    }

    public function testAnonIndex() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/pln/1/au/1/status/');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testUserIndex() {
        $client = $this->makeClient(LoadUser::USER);
        $crawler = $client->request('GET', '/pln/1/au/1/status/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testAdminIndex() {
        $client = $this->makeClient(LoadUser::ADMIN);
        $crawler = $client->request('GET', '/pln/1/au/1/status/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testAnonShow() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/pln/1/au/1/status/1');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testUserShow() {
        $client = $this->makeClient(LoadUser::USER);
        $crawler = $client->request('GET', '/pln/1/au/1/status/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testAdminShow() {
        $client = $this->makeClient(LoadUser::ADMIN);
        $crawler = $client->request('GET', '/pln/1/au/1/status/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
