<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\ORM\LoadPln;
use AppBundle\Entity\Pln;
use Nines\UserBundle\DataFixtures\ORM\LoadUser;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class PlnPropertyControllerTest extends BaseTestCase {
    protected function getFixtures() {
        return [
            LoadUser::class,
            LoadPln::class,
        ];
    }

    public function testAnonIndex() : void {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/pln/1/property');
        $this->assertSame(301, $client->getResponse()->getStatusCode());
        $this->assertSame(0, $crawler->selectLink('New')->count());
    }

    public function testUserIndex() : void {
        $client = $this->makeClient(LoadUser::USER);
        $crawler = $client->request('GET', '/pln/1/property');
        $this->assertSame(301, $client->getResponse()->getStatusCode());
        $this->assertSame(0, $crawler->selectLink('New')->count());
    }

    public function testAdminIndex() : void {
        $client = $this->makeClient(LoadUser::ADMIN);
        $crawler = $client->request('GET', '/pln/1/property/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(1, $crawler->selectLink('New')->count());
    }

    public function testAnonEdit() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->em->flush();

        $client = $this->makeClient();
        $crawler = $client->request('GET', '/pln/1/property/org.test/edit');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
    }

    public function testUserEdit() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->em->flush();

        $client = $this->makeClient(LoadUser::USER);
        $crawler = $client->request('GET', '/pln/1/property/org.test/edit');
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminEdit() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->em->flush();

        $client = $this->makeClient(LoadUser::ADMIN);
        $formCrawler = $client->request('GET', '/pln/1/property/org.test/edit');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $form = $formCrawler->selectButton('Update')->form();
        $values = $form->getPhpValues();
        $values['pln_property']['name'] = 'org.lockss.fireball';
        $values['pln_property']['values'][0] = 'first';
        $values['pln_property']['values'][1] = 'second';

        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertTrue($client->getResponse()->isRedirect());
        $responseCrawler = $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(1, $responseCrawler->filter('td:contains("first")')->count());
        $this->assertSame(1, $responseCrawler->filter('td:contains("second")')->count());
    }

    public function testAnonNew() : void {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/pln/1/property/new');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
    }

    public function testUserNew() : void {
        $client = $this->makeClient(LoadUser::USER);
        $crawler = $client->request('GET', '/pln/1/property/new');
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminNew() : void {
        $client = $this->makeClient(LoadUser::ADMIN);
        $formCrawler = $client->request('GET', '/pln/1/property/new');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $form = $formCrawler->selectButton('Create')->form([
            'pln_property[name]' => 'org.lockss.fireball',
            'pln_property[values][0]' => 'true',
        ]);

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $responseCrawler = $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(1, $responseCrawler->filter('td:contains("true")')->count());
    }

    public function testAdminNewNullValue() : void {
        $client = $this->makeClient(LoadUser::ADMIN);
        $formCrawler = $client->request('GET', '/pln/1/property/new');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // If you add a prperty with no values chaos reigns supreme.
        $form = $formCrawler->selectButton('Create')->form([
            'pln_property[name]' => 'org.lockss.fireball',
        ]);

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $responseCrawler = $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(0, $responseCrawler->filter('td:contains("true")')->count());
    }

    public function testAdminNewMultipleValues() : void {
        $client = $this->makeClient(LoadUser::ADMIN);
        $formCrawler = $client->request('GET', '/pln/1/property/new');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $form = $formCrawler->selectButton('Create')->form();
        $values = $form->getPhpValues();
        $values['pln_property']['name'] = 'org.lockss.fireball';
        $values['pln_property']['values'][0] = 'first';
        $values['pln_property']['values'][1] = 'second';

        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertTrue($client->getResponse()->isRedirect());
        $responseCrawler = $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(1, $responseCrawler->filter('td:contains("first")')->count());
        $this->assertSame(1, $responseCrawler->filter('td:contains("second")')->count());
    }

    public function testAnonDelete() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->em->flush();

        $client = $this->makeClient();
        $crawler = $client->request('GET', '/pln/1/property/org.test/delete');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
    }

    public function testUserDelete() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->em->flush();

        $client = $this->makeClient(LoadUser::USER);
        $crawler = $client->request('GET', '/pln/1/property/org.test/delete');
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminDelete() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->em->flush();

        $client = $this->makeClient(LoadUser::ADMIN);
        $crawler = $client->request('GET', '/pln/1/property/org.test/delete');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect());
        $responseCrawler = $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $this->em->clear();
        $pln = $this->em->find(Pln::class, 1);
        $this->assertNull($pln->getProperty('org.test'));
    }
}
