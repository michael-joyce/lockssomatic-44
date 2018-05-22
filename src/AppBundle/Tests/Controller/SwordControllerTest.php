<?php

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\ORM\LoadContentProvider;
use AppBundle\DataFixtures\ORM\LoadDeposit;
use AppBundle\DataFixtures\ORM\LoadPluginProperty;
use AppBundle\Entity\Au;
use AppBundle\Entity\Deposit;
use AppBundle\Utilities\Namespaces;
use Exception;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use SimpleXMLElement;

class SwordControllerTest extends BaseTestCase {

    protected function getFixtures() {
        return array(
            LoadContentProvider::class,
            LoadPluginProperty::class,
            LoadDeposit::class,
        );
    }

    private function getXml($string) {
        $xml = simplexml_load_string($string);
        Namespaces::registerNamespaces($xml);
        return $xml;
    }

    private function assertXpath($expected, SimpleXMLElement $xml, $xpath, $method = 'assertEquals') {
        $this->$method($expected, (string) ($xml->xpath($xpath)[0]));
    }

    private function getData($filename) {
        $path = dirname(dirname(__FILE__)) . '/data/' . $filename;
        if (!file_exists($path)) {
            throw new Exception("Cannot find data file {$path}");
        }
        $data = file_get_contents($path);
        if ($data === false) {
            throw new Exception("Cannot read data file {$path}");
        }
        return $data;
    }

    public function testServiceDocumentMissingOnBehalfOf() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/api/sword/2.0/sd-iri');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $xml = $this->getXml($client->getResponse()->getContent());
        $this->assertXpath(
                'HTTP header On-Behalf-Of is required.', $xml, '/sword:error/atom:summary'
        );
        $this->assertXpath(400, $xml, '/sword:error/atom:summary/@code');
    }

    public function testServiceDocumentProviderNotFound() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
            'HTTP_ON-Behalf-Of' => 'abc123',
        ));
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $xml = $this->getXml($client->getResponse()->getContent());
        $this->assertXpath(
                'Content provider not found.', $xml, '/sword:error/atom:summary'
        );
        $this->assertXpath(404, $xml, '/sword:error/atom:summary/@code');
    }

    public function testServiceDocument() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
            'HTTP_On-Behalf-Of' => LoadContentProvider::PROVIDER_UUID_1,
        ));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $xml = $this->getXml($client->getResponse()->getContent());
        $this->assertXpath(
                'com.example.text', $xml, '//lom:pluginIdentifier/@id'
        );
        $this->assertXpath(
                '/api/sword/2.0/col-iri/29125DE2-E622-416C-93EB-E887B2A3126C', $xml, '//app:collection/@href', 'assertStringEndsWith'
        );
    }

    public function testCreateDepositSingle() {
        $auCount = count($this->em->getRepository(Au::class)->findAll());
        $provider = $this->getReference('provider.1');
        $client = static::createClient();
        $data = $this->getData('Sword/depositSingle.xml');
        $crawler = $client->request(
                'POST', '/api/sword/2.0/col-iri/' . $provider->getUuid(), array(), array(), array(), $data
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertStringEndsWith('/edit', $response->headers->get('Location'));
        $this->assertEquals($auCount+1, count($this->em->getRepository(Au::class)->findAll()));
    }

    public function testCreateDeposits() {
        $auCount = count($this->em->getRepository(Au::class)->findAll());
        $provider = $this->getReference('provider.1');
        $client = static::createClient();
        $data = $this->getData('Sword/depositSingle.xml');
        $crawler = $client->request(
                'POST', '/api/sword/2.0/col-iri/' . $provider->getUuid(), array(), array(), array(), $data
        );
        $this->assertEquals($auCount+1, count($this->em->getRepository(Au::class)->findAll()));
        $crawler = $client->request(
                'POST', '/api/sword/2.0/col-iri/' . $provider->getUuid(), array(), array(), array(), $data
        );
        // same AU.
        $this->assertEquals($auCount+1, count($this->em->getRepository(Au::class)->findAll()));
    }

    public function testCreateEmptyDeposit() {
        $provider = $this->getReference('provider.1');
        $client = static::createClient();
        $data = $this->getData('Sword/depositSingle.xml');
        $xml = simplexml_load_string($data);
        Namespaces::registerNamespaces($xml);
        $node = $xml->xpath('//lom:content')[0];
        unset($node[0]);

        $crawler = $client->request(
                'POST', '/api/sword/2.0/col-iri/' . $provider->getUuid(), array(), array(), array(), $xml->asXML()
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertContains('Empty deposits are not allowed.', $response->getContent());
    }

    public function testCreateDepositWrongPermissionUrl() {
        $provider = $this->getReference('provider.1');
        $client = static::createClient();
        $data = $this->getData('Sword/depositSingle.xml');
        $data = preg_replace('/example/', 'otherdomain', $data);

        $crawler = $client->request(
                'POST', '/api/sword/2.0/col-iri/' . $provider->getUuid(), array(), array(), array(), $data
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertContains('Permission host for', $response->getContent());
    }

    public function testCreateLargeDeposit() {
        $provider = $this->getReference('provider.1');
        $client = static::createClient();
        $data = $this->getData('Sword/depositSingle.xml');
        $xml = simplexml_load_string($data);
        Namespaces::registerNamespaces($xml);
        $node = $xml->xpath('//lom:content')[0];
        $node['size'] = 1000000; // ONE MILLION K-BYTES!

        $crawler = $client->request(
                'POST', '/api/sword/2.0/col-iri/' . $provider->getUuid(), array(), array(), array(), $xml->asXML()
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertContains('Content size 1000000 exceeds provider&#039;s maximum: 10000', $response->getContent());
    }

    public function testEditDeposit() {
        // first create the deposit.
        $provider = $this->getReference('provider.1');
        $client = static::createClient();
        $data = $this->getData('Sword/depositSingle.xml');
        $createCrawler = $client->request(
                'POST', '/api/sword/2.0/col-iri/' . $provider->getUuid(), array(), array(), array(), $data
        );
        $createResponse = $client->getResponse();
        $this->assertEquals(201, $createResponse->getStatusCode());
        $location = $createResponse->headers->get('Location');
        $this->assertStringEndsWith('/edit', $location);

        // then edit the deposit.
        $update = $this->getData('Sword/depositEdit.xml');
        $editCrawler = $client->request(
                'PUT', preg_replace('~^./api/~', '/api/', $location), array(), array(), array(), $update
        );
        $editResponse = $client->getResponse();
        $this->assertEquals(200, $editResponse->getStatusCode());
        $this->assertStringEndsWith('/edit', $editResponse->headers->get('Location'));

        // check that the content checksum value changed.
        $deposit = $this->em->getRepository(Deposit::class)->findOneBy(array(
            'url' => 'http://example.com/3691/11186563486_8796f4f843_o_d.jpg',
        ));
        $this->assertEquals('D3B0738', $deposit->getChecksumValue());
    }

    public function testViewDeposit() {
        // first create the deposit.
        $provider = $this->getReference('provider.1');
        $deposit = $this->getReference('deposit.1');
        $client = static::createClient();
        $crawler = $client->request(
                'GET', '/api/sword/2.0/cont-iri/' . $provider->getUuid() . '/' . $deposit->getUuid()
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testStatement() {
        // first create the deposit.
        $provider = $this->getReference('provider.1');
        $deposit = $this->getReference('deposit.1');
        $client = static::createClient();
        $crawler = $client->request(
                'GET', '/api/sword/2.0/cont-iri/' . $provider->getUuid() . '/' . $deposit->getUuid() . '/state'
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testReceipt() {
        // first create the deposit.
        $provider = $this->getReference('provider.1');
        $deposit = $this->getReference('deposit.1');
        $client = static::createClient();
        $crawler = $client->request(
                'GET', '/api/sword/2.0/cont-iri/' . $provider->getUuid() . '/' . $deposit->getUuid() . '/edit'
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

}
