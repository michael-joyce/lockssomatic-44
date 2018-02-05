<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Utilities\Namespaces;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SwordControllerTest extends WebTestCase
{
    
    private function getXml($string) {
        $xml = simplexml_load_string($string);
        Namespaces::registerNamespaces($xml);
        return $xml;
    }
    
    private function assertXpath($expected, SimpleXMLElement $xml, $xpath, $method = 'assertEquals') {
        $this->$method($expected, (string) ($xml->xpath($xpath)[0]));
    }    
    
    public function testServiceDocumentMissingOnBehalfOf() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/api/sword/2.0/sd-iri');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $xml = $this->getXml($client->getResponse()->getContent());
        $this->assertXpath(
            'HTTP header On-Behalf-Of is required.',
            $xml,
            '/sword:error/atom:summary'
        );
        $this->assertXpath(400, $xml, '/sword:error/atom:summary/@code');
    }
    
}
