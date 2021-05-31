<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use App\DataFixtures\ContentProviderFixtures;
use App\DataFixtures\DepositFixtures;
use App\DataFixtures\PluginPropertyFixtures;
use App\Entity\Au;
use App\Entity\Deposit;
use App\Utilities\Namespaces;
use Exception;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use SimpleXMLElement;

class SwordControllerTest extends ControllerBaseCase {
    private function getXml($string) {
        $xml = simplexml_load_string($string);
        Namespaces::registerNamespaces($xml);

        return $xml;
    }

    private function assertXpath($expected, SimpleXMLElement $xml, $xpath, $method = 'assertEquals') : void {
        $this->{$method}($expected, (string) ($xml->xpath($xpath)[0]));
    }

    private function getData($filename) {
        $path = dirname(__FILE__, 2) . '/data/' . $filename;
        if ( ! file_exists($path)) {
            throw new Exception("Cannot find data file {$path}");
        }
        $data = file_get_contents($path);
        if (false === $data) {
            throw new Exception("Cannot read data file {$path}");
        }

        return $data;
    }

    protected function fixtures() : array {
        return [
            ContentProviderFixtures::class,
            PluginPropertyFixtures::class,
            DepositFixtures::class,
        ];
    }

    public function testServiceDocumentMissingOnBehalfOf() : void {
        $crawler = $this->client->request('GET', '/api/sword/2.0/sd-iri');
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
        $xml = $this->getXml($this->client->getResponse()->getContent());
        $this->assertXpath(
            'HTTP header On-Behalf-Of is required.',
            $xml,
            '/sword:error/atom:summary'
        );
        $this->assertXpath(400, $xml, '/sword:error/atom:summary/@code');
    }

    public function testServiceDocumentProviderNotFound() : void {
        $crawler = $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_ON-Behalf-Of' => 'abc123',
        ]);
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
        $xml = $this->getXml($this->client->getResponse()->getContent());
        $this->assertXpath(
            'Content provider not found.',
            $xml,
            '/sword:error/atom:summary'
        );
        $this->assertXpath(404, $xml, '/sword:error/atom:summary/@code');
    }

    public function testServiceDocument() : void {
        $crawler = $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => ContentProviderFixtures::PROVIDER_UUID_1,
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $xml = $this->getXml($this->client->getResponse()->getContent());
        $this->assertXpath(
            'com.example.text',
            $xml,
            '//lom:pluginIdentifier/@id'
        );
        $this->assertXpath(
            '/api/sword/2.0/col-iri/29125DE2-E622-416C-93EB-E887B2A3126C',
            $xml,
            '//app:collection/@href',
            'assertStringEndsWith'
        );
    }

    public function testCreateDepositSingle() : void {
        $auCount = count($this->entityManager->getRepository(Au::class)->findAll());
        $provider = $this->getReference('provider.1');
        $data = $this->getData('Sword/depositSingle.xml');
        $crawler = $this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $provider->getUuid(),
            [],
            [],
            [],
            $data
        );
        $response = $this->client->getResponse();
        $this->assertSame(201, $response->getStatusCode());
        $this->assertStringEndsWith('/edit', $response->headers->get('Location'));
        $this->assertSame($auCount + 1, count($this->entityManager->getRepository(Au::class)->findAll()));
    }

    public function testCreateDepositMultiple() : void {
        $auCount = count($this->entityManager->getRepository(Au::class)->findAll());
        $provider = $this->getReference('provider.1');
        $data = $this->getData('Sword/depositMultiple.xml');
        $crawler = $this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $provider->getUuid(),
            [],
            [],
            [],
            $data
        );
        $response = $this->client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('multiple content elements are not allowed.', $response->getContent());
    }

    public function testCreateDeposits() : void {
        $auCount = count($this->entityManager->getRepository(Au::class)->findAll());
        $provider = $this->getReference('provider.1');
        $data = $this->getData('Sword/depositSingle.xml');
        $crawler = $this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $provider->getUuid(),
            [],
            [],
            [],
            $data
        );
        $this->assertSame($auCount + 1, count($this->entityManager->getRepository(Au::class)->findAll()));
        $crawler = $this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $provider->getUuid(),
            [],
            [],
            [],
            $data
        );
        // same AU.
        $this->assertSame($auCount + 1, count($this->entityManager->getRepository(Au::class)->findAll()));
    }

    public function testCreateEmptyDeposit() : void {
        $provider = $this->getReference('provider.1');
        $data = $this->getData('Sword/depositSingle.xml');
        $xml = simplexml_load_string($data);
        Namespaces::registerNamespaces($xml);
        $node = $xml->xpath('//lom:content')[0];
        unset($node[0]);

        $crawler = $this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $provider->getUuid(),
            [],
            [],
            [],
            $xml->asXML()
        );
        $response = $this->client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Empty deposits are not allowed.', $response->getContent());
    }

    public function testCreateDepositWrongPermissionUrl() : void {
        $provider = $this->getReference('provider.1');
        $data = $this->getData('Sword/depositSingle.xml');
        $data = preg_replace('/example/', 'otherdomain', $data);

        $crawler = $this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $provider->getUuid(),
            [],
            [],
            [],
            $data
        );
        $response = $this->client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Permission host for', $response->getContent());
    }

    public function testCreateLargeDeposit() : void {
        $provider = $this->getReference('provider.1');
        $data = $this->getData('Sword/depositSingle.xml');
        $xml = simplexml_load_string($data);
        Namespaces::registerNamespaces($xml);
        $node = $xml->xpath('//lom:content')[0];
        $node['size'] = 1000000; // ONE MILLION K-BYTES!

        $crawler = $this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $provider->getUuid(),
            [],
            [],
            [],
            $xml->asXML()
        );
        $response = $this->client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Content size 1000000 exceeds provider&#039;s maximum: 10000', $response->getContent());
    }

    public function testEditDeposit() : void {
        // first create the deposit.
        $provider = $this->getReference('provider.1');
        $data = $this->getData('Sword/depositSingle.xml');
        $createCrawler = $this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $provider->getUuid(),
            [],
            [],
            [],
            $data
        );
        $createResponse = $this->client->getResponse();
        $this->assertSame(201, $createResponse->getStatusCode());
        $location = $createResponse->headers->get('Location');
        $this->assertStringEndsWith('/edit', $location);

        // then edit the deposit.
        $update = $this->getData('Sword/depositEdit.xml');
        $editCrawler = $this->client->request(
            'PUT',
            preg_replace('~^./api/~', '/api/', $location),
            [],
            [],
            [],
            $update
        );
        $editResponse = $this->client->getResponse();
        $this->assertSame(200, $editResponse->getStatusCode());
        $this->assertStringEndsWith('/edit', $editResponse->headers->get('Location'));

        // check that the content checksum value changed.
        $deposit = $this->entityManager->getRepository(Deposit::class)->findOneBy([
            'url' => 'http://example.com/3691/11186563486_8796f4f843_o_d.jpg',
        ]);
        $this->assertSame('D3B0738', $deposit->getChecksumValue());
    }

    public function testViewDeposit() : void {
        // first create the deposit.
        $provider = $this->getReference('provider.1');
        $deposit = $this->getReference('deposit.1');
        $crawler = $this->client->request(
            'GET',
            '/api/sword/2.0/cont-iri/' . $provider->getUuid() . '/' . $deposit->getUuid()
        );
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testStatementInProgress() : void {
        // first create the deposit.
        $provider = $this->getReference('provider.1');
        $deposit = $this->getReference('deposit.1');
        $crawler = $this->client->request(
            'GET',
            '/api/sword/2.0/cont-iri/' . $provider->getUuid() . '/' . $deposit->getUuid() . '/state'
        );
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('LOCKSS boxes have not completed', $response->getContent());
    }

    public function testStatementComplete() : void {
        // first create the deposit.
        $provider = $this->getReference('provider.1');
        $deposit = $this->getReference('deposit.2');
        $crawler = $this->client->request(
            'GET',
            '/api/sword/2.0/cont-iri/' . $provider->getUuid() . '/' . $deposit->getUuid() . '/state'
        );
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('LOCKSS boxes have harvested', $response->getContent());
    }

    public function testReceipt() : void {
        // first create the deposit.
        $provider = $this->getReference('provider.1');
        $deposit = $this->getReference('deposit.2');
        $crawler = $this->client->request(
            'GET',
            '/api/sword/2.0/cont-iri/' . $provider->getUuid() . '/' . $deposit->getUuid() . '/edit'
        );
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Content URLs deposited', $response->getContent());
    }
}
