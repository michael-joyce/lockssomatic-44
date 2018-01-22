<?php

namespace AppBundle\Tests\Services;

use AppBundle\Entity\Content;
use AppBundle\Services\ContentBuilder;
use AppBundle\Utilities\Namespaces;
use DateTime;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use SimpleXMLElement;

class ContentBuilderTest extends BaseTestCase {

    /**
     * @var ContentBuilder
     */
    private $builder;

    protected function setUp() {
        parent::setUp();
        $this->builder = $this->container->get('AppBundle\Services\ContentBuilder');
    }

    public function testInstace() {
        $this->assertInstanceOf(ContentBuilder::class, $this->builder);
    }

    public function testFromArrayNoTitle() {
        $data = $this->getArrayData();
        $content = $this->builder->fromArray($data);
        $this->assertInstanceOf(Content::class, $content);
        $this->assertEquals(123, $content->getSize());
        $this->assertEquals('Generated Title', $content->getTitle());
        $this->assertEquals('SHA1', $content->getChecksumType());
        $this->assertEquals('ABC', $content->getChecksumValue());
        $this->assertEquals('http://example.com/path/to/thing', $content->getUrl());
        $this->assertInstanceOf(DateTime::class, $content->getDateDeposited());
        $properties = $content->getProperties();
        $this->assertEquals(6, count($properties));
        foreach ($properties as $key) {
            $this->assertEquals($data[$key], $content->getProperty($key));
        }
    }

    public function testFromArrayWithTitle() {
        $data = $this->getArrayData();
        $data['title'] = 'The big whale';
        $content = $this->builder->fromArray($data);
        $this->assertInstanceOf(Content::class, $content);
        $this->assertEquals(123, $content->getSize());
        $this->assertEquals('The big whale', $content->getTitle());
        $this->assertEquals('SHA1', $content->getChecksumType());
        $this->assertEquals('ABC', $content->getChecksumValue());
        $this->assertEquals('http://example.com/path/to/thing', $content->getUrl());
        $this->assertInstanceOf(DateTime::class, $content->getDateDeposited());
        $properties = $content->getProperties();
        $this->assertEquals(7, count($properties));
        foreach ($properties as $key) {
            $this->assertEquals($data[$key], $content->getProperty($key));
        }
    }

    private function getArrayData() {
        return [
            'size' => 123,
            'checksum type' => 'SHA1',
            'checksum value' => 'ABC',
            'url' => 'http://example.com/path/to/thing',
            'prop1' => 'value 1',
            'prop2' => 'value 2',
        ];
    }

    public function testFromSimpleXml() {
        $xml = $this->getXmlData();
        $content = $this->builder->fromSimpleXml($xml);
        $this->assertInstanceOf(Content::class, $content);
        $this->assertEquals(123, $content->getSize());
        $this->assertEquals('Hello World', $content->getTitle());
        $this->assertEquals('SHA1', $content->getChecksumType());
        $this->assertEquals('ABC', $content->getChecksumValue());
        $this->assertEquals('http://example.com/path/to/deposit.zip', $content->getUrl());
        $this->assertInstanceOf(DateTime::class, $content->getDateDeposited());

        $this->assertEquals('Hello World', $content->getProperty('journalTitle'));
        $this->assertEquals('PLN Publisher', $content->getProperty('publisher'));
        $this->assertEquals('http://example.com', $content->getProperty('base_url'));
        $this->assertEquals('http://example.com/permission', $content->getProperty('permission_url'));
        $this->assertEquals(3, $content->getProperty('container_number'));
        $this->assertEquals('http://example.com', $content->getProperty('base_url'));
    }

    private function getXmlData() {
        $str = <<<XML
<content 
    xmlns="http://lockssomatic.info/SWORD2" xmlns:pkp="http://pkp.sfu.ca/SWORD"
    size="123" checksumType="SHA1" checksumValue="ABC"
    pkp:volume="2" pkp:issue="1" pkp:pubDate="2015-01-01"
    pkp:journalTitle="Hello World" pkp:journalUrl="http://hi.example.com"
    pkp:issn="1234-1234" pkp:publisher="PLN Publisher"
    pkp:publisherName="World Publ. Inc." pkp:publisherUrl="http://wp.example.com">
        http://example.com/path/to/deposit.zip
        <property name="base_url" value="http://example.com"/>
        <property name="permission_url" value="http://example.com/permission"/>
        <property name="container_number" value="3"/>
</content>
XML;
        $xml = new SimpleXMLElement($str);
        $ns = new Namespaces();
        $ns->registerNamespaces($xml);
        return $xml;
    }

}
