<?php

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\ORM\LoadContentProvider;
use AppBundle\Utilities\Namespaces;
use Exception;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use SimpleXMLElement;

class SwordControllerTest extends BaseTestCase {

    protected function getFixtures() {
        return array(
            LoadContentProvider::class,
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
        $path = dirname(dirname(__FILE__)).'/data/'.$filename;
        if( !file_exists($path)) {
            throw new Exception("Cannot find data file {$path}");
        }
        $data = file_get_contents($path);
        if($data === false) {
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
        $provider = $this->getReference('provider.1');
        $client = static::createClient();
        $data = $this->getData('Sword/depositSingle.xml');
        $crawler = $client->request(
                'POST', 
                '/api/sword/2.0/col-iri/' . $provider->getUuid(), array(), array(), array(),
                $data
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertStringEndsWith('/edit', $response->headers->get('Location'));
    }
    
    public function testCreateDepositMultiple() {
        $provider = $this->getReference('provider.1');
        $client = static::createClient();
        $data = $this->getData('Sword/depositMultiple.xml');
        $crawler = $client->request(
                'POST', 
                '/api/sword/2.0/col-iri/' . $provider->getUuid(), array(), array(), array(),
                $data
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertStringEndsWith('/edit', $response->headers->get('Location'));
    }
}
