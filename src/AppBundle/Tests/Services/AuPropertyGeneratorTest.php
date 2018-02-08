<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadDeposit;
use AppBundle\Entity\Au;
use AppBundle\Entity\AuProperty;
use AppBundle\Entity\Content;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Plugin;
use AppBundle\Entity\PluginProperty;
use AppBundle\Services\AuPropertyGenerator;
use AppBundle\Services\PluginImporter;
use DateTime;
use Exception;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of AuPropertyGeneratorTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuPropertyGeneratorTest extends BaseTestCase {

    /**
     * @var AuPropertyGenerator
     */
    private $generator;

    protected function getFixtures() {
        return [
            LoadDeposit::class,
        ];
    }

    public function setUp() {
        parent::setUp();
        $this->generator = $this->container->get(AuPropertyGenerator::class);
    }

    public function testSanity() {
        $this->assertInstanceOf(AuPropertyGenerator::class, $this->generator);
    }

    public function testBuildProperty() {
        $au = new Au();
        $key = 'foobar';
        $value = 'some long complex value.';
        $property = $this->generator->buildProperty($au, $key, $value);
        $this->assertInstanceOf(AuProperty::class, $property);
        $this->assertEquals($key, $property->getPropertyKey());
        $this->assertEquals($value, $property->getPropertyValue());
        $this->assertEquals(1, $au->getAuProperties()->count());
    }

    public function testBuildPropetyChild() {
        $au = new Au();
        $key = 'foobar';
        $value = 'some long complex value.';
        $parent = new AuProperty();
        $property = $this->generator->buildProperty($au, $key, $value, $parent);
        $this->assertTrue($parent->hasChildren());
        $this->assertEquals($parent, $property->getParent());
        $this->assertEquals(1, $au->getAuProperties()->count()); // didn't add parent to AU.
    }

    /**
     * @dataProvider generateStringData
     */
    public function testGenerateString($expected, $value, $map) {
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap($map));

        $this->assertEquals($expected, $this->generator->generateString($au, $value));
    }

    public function generateStringData() {
        return [
            // [expected, value, map]
            ['', '""', []],
            ['Some testing.', '"Some testing."', []],
            ['Some testing.', '"Some %s.", foo', [
                    ['foo', 'testing']
                ]],
            ['Test number 1 etc.', '"Test number %d etc.", bling', [
                    ['bling', 1]
                ]],
            ['Test number 1 etc.', '"Test number %d etc.", bling', [
                    ['bling', 1],
                    ['murphy', 1],
                    ['wilson', 1],
                ]],
            ['Test jango is number 1.', '"Test %s is number %d.", bling, murphy', [
                    ['bling', 'jango'],
                    ['murphy', 1],
                ]],
        ];
    }

    /**
     * @dataProvider generateStringBadData
     * @expectedException Exception
     */
    public function testGenerateBadString($expected, $value, $map) {
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap($map));

        $this->assertEquals($expected, $this->generator->generateString($au, $value));
    }

    public function generateStringBadData() {
        return [
            // [expected, value, map]
            ['', '', []],
            ['Some testing.', '"Some %s."', [
                    ['foo', 'testing']
                ]],
            ['Test number 1 etc.', '"Test number %d etc., bling', [
                    ['bling', 1]
                ]],
        ];
    }

    /**
     * @dataProvider testGenerateSymbolData
     */
    public function testGenerateSymbolString(array $data) {
        $property = $this->createMock(PluginProperty::class);
        $property->method('isList')->will($this->returnValue($data['list']));
        $property->method('getPropertyValue')->will($this->returnValue($data['propValue']));

        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getProperty')->will($this->returnValue($property));
        $plugin->method('getName')->will($this->returnValue('dummy'));

        $au = $this->createMock(Au::class);
        $au->method('getPlugin')->will($this->returnValue($plugin));
        $au->method('getAuPropertyValue')->will($this->returnValueMap($data['auValues']));

        $str = $this->generator->generateSymbol($au, 'testable');
        $this->assertEquals($data['expected'], $str);
    }

    public function testGenerateSymbolData() {
        return [
            [[
            'list' => false,
            'propValue' => '"item is a cheese"',
            'auValues' => [
                ['fromage', 'cheddar'],
            ],
            'expected' => 'item is a cheese',
                ]],
            [[
            'list' => false,
            'propValue' => '"item %s is a cheese", fromage',
            'auValues' => [
                ['fromage', 'cheddar'],
            ],
            'expected' => 'item cheddar is a cheese',
                ]],
            [[
            'list' => false,
            'propValue' => '"Sand is %s and %s", grit, color',
            'auValues' => [
                ['fromage', 'cheddar'],
                ['grit', 'coarse'],
                ['color', 'black']
            ],
            'expected' => 'Sand is coarse and black',
                ]],
            [[
            'list' => true,
            'propValue' => [
                '"item %s is a cheese", fromage',
                '"Sand is %s and %s", grit, color',
            ],
            'auValues' => [
                ['fromage', 'cheddar'],
                ['grit', 'coarse'],
                ['color', 'black']
            ],
            'expected' => [
                'item cheddar is a cheese',
                'Sand is coarse and black'
            ],
                ]],
            [[
            'list' => true,
            'propValue' => [
                '"item %s is a cheese", fromage',
                '"Sand is %s and %s", fromage, fromage',
            ],
            'auValues' => [
                ['fromage', 'cheddar'],
                ['grit', 'coarse'],
                ['color', 'black']
            ],
            'expected' => [
                'item cheddar is a cheese',
                'Sand is cheddar and cheddar'
            ],
                ]],
        ];
    }

    public function buildContentItems(Au $au) {
        $deposit = $this->em->find(Deposit::class, 1);
        for ($i = 0; $i < 10; $i++) {
            $content = new Content();
            $content->setUrl("http://example.com/path/{$i}");
            $content->setTitle("Item {$i}");
            $content->setDateDeposited(new DateTime());

            // definitional
            $content->setProperty('base_url', 'http://example.com/path');
            $content->setProperty('container_number', 1);
            $content->setProperty('permission_url', "http://example.com/permission/");
            $content->setProperty('manifest_url', "http://example.com/manifest/");
            //other properties.
            $content->setProperty('journalTitle', 'Journal Title');
            $content->setProperty('publisher', 'Journal Publisher');

            // deposit
            $content->setDeposit($deposit);
            $deposit->addContent($content);
            $content->setAu($au);
            $au->addContent($content);
            $this->em->persist($content);
        }
    }

    public function testBaseProperties() {
        $au = new Au();
        $root = new AuProperty();
        $content = new Content();
        $content->setTitle("Content");
        $content->setProperty('journalTitle', "Fooooo");
        $deposit = new Deposit();
        $deposit->setTitle("Deposit Title");
        $content->setDeposit($deposit);
        $plugin = new Plugin();
        $plugin->setIdentifier('com.example.plugin');
        $au->setPlugin($plugin);
        $content->setProperty('publisher', 'Publishing House');

        $this->generator->baseProperties($au, $root, $content);
        $this->assertEquals(4, count($au->getAuProperties()));
        $this->assertEquals('Fooooo', $au->getSimpleAuProperty('journalTitle'));
        $this->assertEquals('LOCKSSOMatic AU Content Deposit Title', $au->getSimpleAuProperty('title'));
        $this->assertEquals('com.example.plugin', $au->getSimpleAuProperty('plugin'));
        $this->assertEquals('Publishing House', $au->getSimpleAuProperty('attributes.publisher'));
    }

    public function testConfigProperties() {
        $xml = simplexml_load_string($this->xmlData());
        $importer = $this->container->get(PluginImporter::class);
        $plugin = $importer->buildPlugin($xml);
        $au = new Au();
        $au->setPlugin($plugin);
        $content = new Content();
        $content->setUrl("http://example.com/path/item");
        $content->setTitle("Item");
        $content->setDateDeposited(new DateTime());

        // definitional
        $content->setProperty('base_url', 'http://example.com/path');
        $content->setProperty('container_number', 1);
        $content->setProperty('permission_url', "http://example.com/permission/");
        $content->setProperty('manifest_url', "http://example.com/manifest/");
        
        $propertyNames = ['base_url', 'container_number', 'permission_url', 'manifest_url'];
        $root = new AuProperty();
        $root->setAu($au);
        $au->addAuProperty($root);
        
        $this->generator->configProperties($propertyNames, $au, $root, $content);
        // 1 for root, 3 for each property (one to group, one key, one value)
        $this->assertEquals(13, count($au->getAuProperties())); 
        $this->assertEquals('http://example.com/path', $au->getAuPropertyValue('base_url'));
        $this->assertEquals(1, $au->getAuPropertyValue('container_number'));
        $this->assertEquals('http://example.com/permission/', $au->getAuPropertyValue('permission_url'));
        $this->assertEquals('http://example.com/manifest/', $au->getAuPropertyValue('manifest_url'));
    }
    
    public function testContentProperties() {
        $au = new Au();
        $root = new AuProperty();
        $au->addAuProperty($root);
        $content = new Content();
        $content->setProperty("foo", "barr");
        $content->setProperty("spackle", "made from dust.");
        $this->generator->contentProperties($au, $root, $content);
        $this->assertEquals(3, count($au->getAuProperties()));
        $this->assertEquals('barr', $au->getSimpleAuProperty('attributes.pkppln.foo'));
        $this->assertEquals('made from dust.', $au->getSimpleAuProperty('attributes.pkppln.spackle'));
    }

    public function testGenerateProperties() {
        $xml = simplexml_load_string($this->xmlData());
        $importer = $this->container->get(PluginImporter::class);
        $plugin = $importer->buildPlugin($xml);
        $au = new Au();
        $au->setPlugin($plugin);
        $this->buildContentItems($au);

        $this->generator->generateProperties($au);
        $this->assertEquals(23, count($au->getAuProperties()));
        $this->assertEquals('http://example.com/path', $au->getAuPropertyValue('base_url'));
        $this->assertEquals(1, $au->getAuPropertyValue('container_number'));
        $this->assertEquals('http://example.com/permission/', $au->getAuPropertyValue('permission_url'));
        $this->assertEquals('http://example.com/manifest/', $au->getAuPropertyValue('manifest_url'));
    }

    public function xmlData() {
        return <<<'ENDXML'
<map>
    <entry>
        <string>plugin_config_props</string>
        <list>
            <org.lockss.daemon.ConfigParamDescr>
                <key>base_url</key>
                <displayName>Base URL</displayName>
                <description>Usually of the form http://&lt;journal-name&gt;.com/</description>
                <type>3</type>
                <size>40</size>
                <definitional>true</definitional>
                <defaultOnly>false</defaultOnly>
            </org.lockss.daemon.ConfigParamDescr>
            <org.lockss.daemon.ConfigParamDescr>
                <key>container_number</key>
                <displayName>Container No.</displayName>
                <description>WestVault content is organized in containers of a similar size</description>
                <type>6</type>
                <size>8</size>
                <definitional>true</definitional>
                <defaultOnly>false</defaultOnly>
            </org.lockss.daemon.ConfigParamDescr>
            <org.lockss.daemon.ConfigParamDescr>
                <key>manifest_url</key>
                <displayName>Manifest URL</displayName>
                <description>URL for the manifest file (generated by LOCKSSOMatic) for this AU</description>
                <type>3</type>
                <size>200</size>
                <definitional>true</definitional>
                <defaultOnly>false</defaultOnly>
            </org.lockss.daemon.ConfigParamDescr>
            <org.lockss.daemon.ConfigParamDescr>
                <key>permission_url</key>
                <displayName>LOCKSS Permission Url</displayName>
                <description>URL for the LOCKSS permission statement on the server hosting the content</description>
                <type>3</type>
                <size>200</size>
                <definitional>true</definitional>
                <defaultOnly>false</defaultOnly>
            </org.lockss.daemon.ConfigParamDescr>
        </list>
    </entry>
    <entry>
        <string>plugin_version</string>
        <string>1</string>
    </entry>
    <entry>
        <string>au_name</string>
        <string>"Preserved content from WestVault, part %d", container_number</string>
    </entry>
    <entry>
        <string>au_permission_url</string>
        <list>
            <string>"%s", manifest_url</string>
            <string>"%s", permission_url</string>
        </list>
    </entry>
    <entry>
        <string>au_start_url</string>
        <list>
            <string>"%s", manifest_url</string>
            <string>"%s", permission_url</string>
        </list>
    </entry>
    <entry>
        <string>au_def_new_content_crawl</string>
        <long>300000</long>
    </entry>
    <entry>
        <string>au_def_pause_time</string>
        <long>6000</long>
    </entry>
    <entry>
        <string>plugin_name</string>
        <string>COPPUL WestVault Plugin</string>
    </entry>
    <entry>
        <string>bad_entry</string>
        <string>This is bad entry because it has a duplicate key.</string>
    </entry>
    <entry>
        <string>bad_entry</string>
        <string>This is bad entry because it has a duplicate key.</string>
    </entry>
    <entry>
        <string>au_refetch_depth</string>
        <int>999</int>
    </entry>
    <entry>
        <string>plugin_identifier</string>
        <string>ca.sfu.lib.plugin.coppul.WestVaultPlugin</string>
    </entry>
    <entry>
        <string>au_crawlrules</string>
        <list>
            <string>1,"^%sfetch/[^/]+/[^/]+\.zip$", base_url</string>
            <string>1,"^%s$", manifest_url</string>
            <string>1,"^%s$", permission_url</string>
        </list>
    </entry>
    <entry>
        <string>other_bad_entry</string>
        <list>
            <string>foo</string>
            <string>bar</string>
        </list>
    </entry>
    <entry>
        <string>other_bad_entry</string>
        <list>
            <string>foo</string>
            <string>bar</string>
        </list>
    </entry>
    <entry>
        <string>plugin_crawl_type</string>
        <string>HTML Links</string>
    </entry>
</map>
ENDXML;
    }

}
