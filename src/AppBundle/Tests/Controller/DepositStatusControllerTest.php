<?php

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\ORM\LoadDepositStatus;
use AppBundle\Entity\DepositStatus;
use Nines\UserBundle\DataFixtures\ORM\LoadUser;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class DepositStatusControllerTest extends BaseTestCase {

    protected function getFixtures() {
        return [
            LoadUser::class,
            LoadDepositStatus::class
        ];
    }

    public function testAnonIndex() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/pln/1/deposit/1/status/');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testUserIndex() {
        $client = $this->makeClient([
            'username' => 'user@example.com',
            'password' => 'secret',
        ]);
        $crawler = $client->request('GET', '/pln/1/deposit/1/status/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testAdminIndex() {
        $client = $this->makeClient([
            'username' => 'admin@example.com',
            'password' => 'supersecret',
        ]);
        $crawler = $client->request('GET', '/pln/1/deposit/1/status/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testAnonShow() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/pln/1/deposit/1/status/1');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testUserShow() {
        $client = $this->makeClient([
            'username' => 'user@example.com',
            'password' => 'secret',
        ]);
        $crawler = $client->request('GET', '/pln/1/deposit/1/status/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testAdminShow() {
        $client = $this->makeClient([
            'username' => 'admin@example.com',
            'password' => 'supersecret',
        ]);
        $crawler = $client->request('GET', '/pln/1/deposit/1/status/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
