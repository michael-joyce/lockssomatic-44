<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use App\DataFixtures\PlnFixtures;
use App\Entity\Pln;
use Nines\UserBundle\DataFixtures\UserFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class PlnPropertyControllerTest extends ControllerBaseCase {
    protected function fixtures() : array {
        return [
            UserFixtures::class,
            PlnFixtures::class,
        ];
    }

    public function testAnonIndex() : void {
        $crawler = $this->client->request('GET', '/pln/1/property');
        $this->assertSame(301, $this->client->getResponse()->getStatusCode());
        $this->assertSame(0, $crawler->selectLink('New')->count());
    }

    public function testUserIndex() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/property');
        $this->assertSame(301, $this->client->getResponse()->getStatusCode());
        $this->assertSame(0, $crawler->selectLink('New')->count());
    }

    public function testAdminIndex() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/property/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(1, $crawler->selectLink('New')->count());
    }

    public function testAnonEdit() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/pln/1/property/org.test/edit');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserEdit() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->entityManager->flush();

        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/property/org.test/edit');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminEdit() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->entityManager->flush();

        $this->login('user.admin');
        $formCrawler = $this->client->request('GET', '/pln/1/property/org.test/edit');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $form = $formCrawler->selectButton('Update')->form();
        $values = $form->getPhpValues();
        $values['pln_property']['name'] = 'org.lockss.fireball';
        $values['pln_property']['values'][0] = 'first';
        $values['pln_property']['values'][1] = 'second';

        $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $responseCrawler = $this->client->followRedirect();
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(1, $responseCrawler->filter('td:contains("first")')->count());
        $this->assertSame(1, $responseCrawler->filter('td:contains("second")')->count());
    }

    public function testAnonNew() : void {
        $crawler = $this->client->request('GET', '/pln/1/property/new');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserNew() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/property/new');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminNew() : void {
        $this->login('user.admin');
        $formCrawler = $this->client->request('GET', '/pln/1/property/new');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $form = $formCrawler->selectButton('Create')->form([
            'pln_property[name]' => 'org.lockss.fireball',
            'pln_property[values][0]' => 'true',
        ]);

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $responseCrawler = $this->client->followRedirect();
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(1, $responseCrawler->filter('td:contains("true")')->count());
    }

    public function testAdminNewNullValue() : void {
        $this->login('user.admin');
        $formCrawler = $this->client->request('GET', '/pln/1/property/new');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        // If you add a prperty with no values chaos reigns supreme.
        $form = $formCrawler->selectButton('Create')->form([
            'pln_property[name]' => 'org.lockss.fireball',
        ]);

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $responseCrawler = $this->client->followRedirect();
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(0, $responseCrawler->filter('td:contains("true")')->count());
    }

    public function testAdminNewMultipleValues() : void {
        $this->login('user.admin');
        $formCrawler = $this->client->request('GET', '/pln/1/property/new');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $form = $formCrawler->selectButton('Create')->form();
        $values = $form->getPhpValues();
        $values['pln_property']['name'] = 'org.lockss.fireball';
        $values['pln_property']['values'][0] = 'first';
        $values['pln_property']['values'][1] = 'second';

        $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $responseCrawler = $this->client->followRedirect();
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(1, $responseCrawler->filter('td:contains("first")')->count());
        $this->assertSame(1, $responseCrawler->filter('td:contains("second")')->count());
    }

    public function testAnonDelete() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/pln/1/property/org.test/delete');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserDelete() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->entityManager->flush();

        $this->login('user.user');
        $crawler = $this->client->request('GET', '/pln/1/property/org.test/delete');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminDelete() : void {
        $pln = $this->getReference('pln.1');
        $pln->setProperty('org.test', 'this is a test.');
        $this->entityManager->flush();

        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/pln/1/property/org.test/delete');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $responseCrawler = $this->client->followRedirect();
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->entityManager->clear();
        $pln = $this->entityManager->find(Pln::class, 1);
        $this->assertNull($pln->getProperty('org.test'));
    }
}
