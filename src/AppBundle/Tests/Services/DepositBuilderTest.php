<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Services\DepositBuilder;
use AppBundle\Utilities\Namespaces;
use DateTime;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use SimpleXMLElement;

/**
 * Description of DepositBuilderTest
 */
class DepositBuilderTest extends BaseTestCase {
    
    /**
     * @var DepositBuilder
     */
    private $builder;
    
    protected function getFixtures() {
        return [
            LoadContentProvider::class,
        ];
    }
    
    protected function setUp() {
        parent::setUp();
        $this->builder = $this->container->get(DepositBuilder::class);
    }
    
    public function testInstance() {
        $this->assertInstanceOf(DepositBuilder::class, $this->builder);
    }
    
    public function testFromXml() {
        $xml = $this->getXml();
        $deposit = $this->builder->fromXml($xml, $this->getReference('provider.1'));
        $this->assertInstanceOf(Deposit::class, $deposit);
        $this->assertEquals('771E96EC-5486-4E34-A1F6-AB113AFB642D', $deposit->getUuid());
        $this->assertInstanceOf(DateTime::class, $deposit->getDateDeposited());
        $this->assertEquals('Test Deposit', $deposit->getTitle());
        $this->assertEquals('', $deposit->getSummary());
    }
    
    public function testFromArray() {
        $data = $this->getArray();
        $deposit = $this->builder->fromArray($data, $this->getReference('provider.1'));
        $this->assertInstanceOf(Deposit::class, $deposit);
        $this->assertEquals('771E96EC-5486-4E34-A1F6-AB113AFB642D', $deposit->getUuid());
        $this->assertInstanceOf(DateTime::class, $deposit->getDateDeposited());
        $this->assertEquals('Deposit 1', $deposit->getTitle());
        $this->assertEquals('Judgement', $deposit->getSummary());
    }
    
    public function testFromArrayNoUuid() {
        $data = $this->getArray();
        unset($data['uuid']);
        $deposit = $this->builder->fromArray($data, $this->getReference('provider.1'));
        $this->assertInstanceOf(Deposit::class, $deposit);
        $this->assertEquals(36, strlen($deposit->getUuid()));
        $this->assertInstanceOf(DateTime::class, $deposit->getDateDeposited());
        $this->assertEquals('Deposit 1', $deposit->getTitle());
        $this->assertEquals('Judgement', $deposit->getSummary());
    }
    
    private function getArray() {
        return [
            'size' => 300,
            'type' => 'add',
            'url' => 'http://example.com/path/to/deposit.zip',
            'checksum type' => 'sha1',
            'checksum value' => 'abc123',
            'uuid' => '771E96EC-5486-4E34-A1F6-AB113AFB642D',
            'title' => 'Deposit 1',
            'summary' => 'Judgement',
        ];
    }

    private function getXml() {
        $str = <<<XML
<entry xmlns="http://www.w3.org/2005/Atom"
        xmlns:dcterms="http://purl.org/dc/terms/"
        xmlns:lom="http://lockssomatic.info/SWORD2"
        xmlns:pkp="http://pkp.sfu.ca/SWORD">
    <title>Test Deposit</title>
    <id>urn:uuid:771E96EC-5486-4E34-A1F6-AB113AFB642D</id>
    <updated>2016-01-01</updated>
    <author><name>J Testing</name></author>
    <summary type="text">
        Content deposited to LOCKSS-O-Matic via the PKP PLN Staging Server.
    </summary>
        <content xmlns="http://lockssomatic.info/SWORD2"
                 xmlns:pkp="http://pkp.sfu.ca/SWORD"
            size="1234"
            checksumType="SHA1"
            checksumValue="abc123123"
            pkp:volume="2"
            pkp:issue="1"
            pkp:pubDate="2015-01-01"
            pkp:journalTitle="Hello World"
            pkp:journalUrl="http://hi.example.com"
            pkp:issn="1234-1234"
            pkp:publisher="PLN Publisher"
            pkp:publisherName="World Publ. Inc."
            pkp:publisherUrl="http://wp.example.com">
                http://example.com/path/to/deposit.zip
                <property name="base_url" value="http://example.com"/>
                <property name="permission_url" value="http://example.com/permission"/>
                <property name="container_number" value="3"/>
        </content>
</entry>
XML;
        $xml = new SimpleXMLElement($str);
        Namespaces::registerNamespaces($xml);
        return $xml;
    }
        
}
