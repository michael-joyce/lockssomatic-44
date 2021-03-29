<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\DataFixtures\ContentProviderFixtures;
use App\Entity\Deposit;
use App\Services\DepositBuilder;
use App\Utilities\Namespaces;
use DateTimeImmutable;
use Exception;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use SimpleXMLElement;

/**
 * Description of DepositBuilderTest.
 */
class DepositBuilderTest extends ControllerBaseCase
{
    /**
     * @var DepositBuilder
     */
    private $builder;

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
        $str = <<<'XML'
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

    protected function fixtures() : array {
        return [
            ContentProviderFixtures::class,
        ];
    }

    public function testInstance() : void {
        $this->assertInstanceOf(DepositBuilder::class, $this->builder);
    }

    public function testFromXml() : void {
        $xml = $this->getXml();
        $deposit = $this->builder->fromXml($xml, $this->getReference('provider.1'));
        $this->assertInstanceOf(Deposit::class, $deposit);
        $this->assertSame('771E96EC-5486-4E34-A1F6-AB113AFB642D', $deposit->getUuid());
        $this->assertInstanceOf(DateTimeImmutable::class, $deposit->getDateDeposited());
        $this->assertSame('Test Deposit', $deposit->getTitle());
        $this->assertNull($deposit->getSummary());
    }

    public function testFromXmlMultipleDeposits() : void {
        $this->expectException(Exception::class);
        $xml = $this->getXml();
        $node = $xml->xpath('/atom:entry')[0];
        $node->addChild('content', null, Namespaces::NS['lom']);
        $deposit = $this->builder->fromXml($xml, $this->getReference('provider.1'));
        $this->assertInstanceOf(Deposit::class, $deposit);
        $this->assertSame('771E96EC-5486-4E34-A1F6-AB113AFB642D', $deposit->getUuid());
        $this->assertInstanceOf(DateTimeImmutable::class, $deposit->getDateDeposited());
        $this->assertSame('Test Deposit', $deposit->getTitle());
        $this->assertSame('', $deposit->getSummary());
    }

    public function testFromArray() : void {
        $data = $this->getArray();
        $deposit = $this->builder->fromArray($data, $this->getReference('provider.1'));
        $this->assertInstanceOf(Deposit::class, $deposit);
        $this->assertSame('771E96EC-5486-4E34-A1F6-AB113AFB642D', $deposit->getUuid());
        $this->assertInstanceOf(DateTimeImmutable::class, $deposit->getDateDeposited());
        $this->assertSame('Deposit 1', $deposit->getTitle());
        $this->assertSame('Judgement', $deposit->getSummary());
    }

    public function testFromArrayNoUuid() : void {
        $data = $this->getArray();
        unset($data['uuid']);
        $deposit = $this->builder->fromArray($data, $this->getReference('provider.1'));
        $this->assertInstanceOf(Deposit::class, $deposit);
        $this->assertSame(36, mb_strlen($deposit->getUuid()));
        $this->assertInstanceOf(DateTimeImmutable::class, $deposit->getDateDeposited());
        $this->assertSame('Deposit 1', $deposit->getTitle());
        $this->assertSame('Judgement', $deposit->getSummary());
    }

    protected function setup() : void {
        parent::setUp();
        $this->builder = self::$container->get(DepositBuilder::class);
    }
}
