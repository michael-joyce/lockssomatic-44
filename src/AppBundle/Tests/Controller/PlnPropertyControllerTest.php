<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\ORM\LoadPln;
use Nines\UserBundle\DataFixtures\ORM\LoadUser;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class PlnPropertyControllerTest extends BaseTestCase {

    protected function getFixtures() {
        return [
            LoadUser::class,
            LoadPln::class,
        ];
    }

    public function testAnonIndex() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/pln/1/property');
        $this->assertEquals(301, $client->getResponse()->getStatusCode());
        $this->assertEquals(0, $crawler->selectLink('New')->count());
    }

    public function testUserIndex() {
        $client = $this->makeClient(LoadUser::USER);
        $crawler = $client->request('GET', '/pln/1/property');
        $this->assertEquals(301, $client->getResponse()->getStatusCode());
        $this->assertEquals(0, $crawler->selectLink('New')->count());
    }

    public function testAdminIndex() {
        $client = $this->makeClient(LoadUser::ADMIN);
        $crawler = $client->request('GET', '/pln/1/property/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->selectLink('New')->count());
    }

//    public function testAnonEdit() {
//        $client = $this->makeClient();
//        $crawler = $client->request('GET', '/pln/1/edit');
//        $this->assertEquals(302, $client->getResponse()->getStatusCode());
//    }
//
//    public function testUserEdit() {
//        $client = $this->makeClient(LoadUser::USER);
//        $crawler = $client->request('GET', '/pln/1/edit');
//        $this->assertEquals(403, $client->getResponse()->getStatusCode());
//    }
//
//    public function testAdminEdit() {
//        $client = $this->makeClient(LoadUser::ADMIN);
//        $formCrawler = $client->request('GET', '/pln/1/edit');
//        $this->assertEquals(200, $client->getResponse()->getStatusCode());
//
//        $form = $formCrawler->selectButton('Update')->form([
//            'pln[name]' => 'fireball',
//            'pln[enableContentUi]' => 0,
//            'pln[contentPort]' => 8123,
//        ]);
//
//        $client->submit($form);
//        $this->assertTrue($client->getResponse()->isRedirect('/pln/1'));
//        $responseCrawler = $client->followRedirect();
//        $this->assertEquals(200, $client->getResponse()->getStatusCode());
//        $this->assertEquals(1, $responseCrawler->filter('td:contains("8123")')->count());
//    }

    public function testAnonNew() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/pln/1/property/new');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testUserNew() {
        $client = $this->makeClient(LoadUser::USER);
        $crawler = $client->request('GET', '/pln/1/property/new');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminNew() {
        $client = $this->makeClient(LoadUser::ADMIN);
        $formCrawler = $client->request('GET', '/pln/1/property/new');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $formCrawler->selectButton('Create')->form([
            'pln_property[name]' => 'org.lockss.fireball',
            'pln_property[values][0]' => 'true'
        ]);

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $responseCrawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $responseCrawler->filter('td:contains("true")')->count());
    }

    public function testAdminNewMultipleValues() {
        $client = $this->makeClient(LoadUser::ADMIN);
        $formCrawler = $client->request('GET', '/pln/1/property/new');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $form = $formCrawler->selectButton('Create')->form();
        $values = $form->getPhpValues();
        $values['pln_property']['name'] = 'org.lockss.fireball';
        $values['pln_property']['values'][0] = 'first';
        $values['pln_property']['values'][1] = 'second';

        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertTrue($client->getResponse()->isRedirect());
        $responseCrawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $responseCrawler->filter('td:contains("first")')->count());
        $this->assertEquals(1, $responseCrawler->filter('td:contains("second")')->count());
    }
    
}
