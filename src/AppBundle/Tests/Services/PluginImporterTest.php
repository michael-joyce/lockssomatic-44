<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadEmptyFixture;
use AppBundle\Entity\Plugin;
use AppBundle\Entity\PluginProperty;
use AppBundle\Services\PluginImporter;
use Exception;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use SimpleXMLElement;
use ZipArchive;

/**
 * Description of PluginImporterTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class PluginImporterTest extends BaseTestCase {
    
    /**
     * @var PluginImporter
     */
    private $importer;
    
    protected function getFixtures() {
        return [];
    }
    
    protected function setUp() {
        parent::setUp();
        $this->importer = $this->container->get(PluginImporter::class);
    }
    
    protected function getArchiveStub() {
        $stub = $this->createMock(ZipArchive::class);
        $stub->method('open')->willReturn(true);
        $entries = [
            PluginImporter::MANIFEST => $this->manifestData(),
            'ca/sfu/lib/plugin/coppul/WestVaultPlugin.xml' => $this->xmlData(),
        ];
        // This should really be returnMap(), but the default arguments to
        // ZipArchive->getFromName() are too weird to map properly. Meh.
        $stub->method('getFromName')->will($this->returnCallback(function($name) use ($entries) {
            if(isset($entries[$name])) {
                return $entries[$name];
            }
            return null;
        }));
        return $stub;
    }
    
    public function testSanity() {
        $this->assertInstanceOf(PluginImporter::class, $this->importer);
    }

    public function testManifestSectionCount() {
        $data = $this->manifestData();
        $manifest = $this->importer->parseManifest($data);
        $this->assertEquals(2, count($manifest));
    }
    
    public function testManifestFirstSection() {
        $data = $this->manifestData();
        $manifest = $this->importer->parseManifest($data);
        $this->assertEquals(array(
            'manifest-version' => '1.0',
            'ant-version' => 'Apache Ant 1.10.1',
            'created-by' => '1.8.0_144-b01 (Oracle Corporation)',
        ), $manifest[0]);
    }
    
    public function testManifestSecondSection() {
        $data = $this->manifestData();
        $manifest = $this->importer->parseManifest($data);
        $this->assertEquals(array(
            'name' => 'ca/sfu/lib/plugin/coppul/WestVaultPlugin.xml',
            'lockss-plugin' => 'true',
            'sha-256-digest' => '1PNUJEn9tHPeDE3qiIgXCzvi6eblJayHNZK1M3YWq30=',
        ), $manifest[1]);
    }
    
    public function testManifestBlankLines() {
        $data = $this->manifestData() . "\n\n\n";
        $manifest = $this->importer->parseManifest($data);
        $this->assertEquals(2, count($manifest));
    }
    
    public function testFindPluginEntries() {
        $data = $this->manifestData();
        $manifest = $this->importer->parseManifest($data);        
        $entries = $this->importer->findPluginEntries($manifest);
        $this->assertEquals(['ca/sfu/lib/plugin/coppul/WestVaultPlugin.xml'], $entries);
    }
    
    public function testFindPluginXml() {
        $stub = $this->getArchiveStub();
        $xml = $this->importer->getPluginXml($stub, 'ca/sfu/lib/plugin/coppul/WestVaultPlugin.xml');
        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
    }
    
    public function testFindXmlPropString() {
        $xml = simplexml_load_string($this->xmlData());
        $this->assertEquals('COPPUL WestVault Plugin', $this->importer->findXmlPropString($xml, 'plugin_name'));
        $this->assertEquals(null, $this->importer->findXmlPropString($xml, 'fancy_dan'));
    }
    
    /**
     * @expectedException Exception
     */
    public function testFindXmlPropStringException() {
        $xml = simplexml_load_string($this->xmlData());
        $this->importer->findXmlPropString($xml, 'bad_entry');
    }
    
    public function testFindXmlPropElement() {
        $xml = simplexml_load_string($this->xmlData());
        $this->assertInstanceOf(SimpleXMLElement::class, $this->importer->findXmlPropElement($xml, 'au_permission_url'));
        $this->assertEquals(null, $this->importer->findXmlPropElement($xml, 'fancy_dan'));
    }
    
    /**
     * @expectedException Exception
     */
    public function testFindXmlPropElementException() {
        $xml = simplexml_load_string($this->xmlData());
        $this->importer->findXmlPropElement($xml, 'other_bad_entry');
    }
    
    public function testNewPluginPropertyString() {
        $xml = simplexml_load_string($this->xmlData());
        $plugin = new Plugin();
        $property = $this->importer->newPluginProperty($plugin, 'plugin_version', $xml->xpath('//entry[string[1]/text()="plugin_version"]/string[2]')[0]);
        $this->assertInstanceOf(PluginProperty::class, $property);
        $this->assertEquals('plugin_version', $property->getPropertyKey());
        $this->assertEquals($plugin, $property->getPlugin());
        $this->assertEquals(1, $property->getPropertyValue());
        $this->assertNull($property->getChildren());
        $this->assertFalse($property->isList());
    }
    
    public function testNewPluginPropertyList() {
        $xml = simplexml_load_string($this->xmlData());
        $plugin = new Plugin();
        $property = $this->importer->newPluginProperty($plugin, 'au_permission_url', $xml->xpath('//entry[string[1]/text()="au_permission_url"]/list[1]')[0]);
        $this->assertInstanceOf(PluginProperty::class, $property);
        $this->assertEquals('au_permission_url', $property->getPropertyKey());
        $this->assertEquals($plugin, $property->getPlugin());
        $this->assertEquals(['"%s", manifest_url', '"%s", permission_url'], $property->getPropertyValue());
        $this->assertNull($property->getChildren());
        $this->assertTrue($property->isList());
    }
    
    public function testImportChildren() {
        $xml = simplexml_load_string($this->xmlData());
        $property = new PluginProperty();
        
        $plugin = new Plugin();        
        $property->setPlugin($plugin);
        $plugin->addPluginProperty($property);
        
        $childProp = $this->importer->importChildren($property, $xml->xpath('//org.lockss.daemon.ConfigParamDescr[1]')[0]);
        $this->assertInstanceOf(PluginProperty::class, $childProp);
        $this->assertEquals($plugin, $childProp->getPlugin());
        $this->assertEquals($property, $childProp->getParent());
        
        $children = $childProp->getChildren();
        $this->assertEquals(7, count($children));
        $this->assertEquals('key', $children[0]->getPropertyKey());
        $this->assertEquals('base_url', $children[0]->getPropertyValue());
        $this->assertEquals('displayName', $children[1]->getPropertyKey());
        $this->assertEquals('Base URL', $children[1]->getPropertyValue());
        $this->assertEquals('description', $children[2]->getPropertyKey());
        $this->assertEquals('Usually of the form http://<journal-name>.com/', $children[2]->getPropertyValue());
        $this->assertEquals('type', $children[3]->getPropertyKey());
        $this->assertEquals('3', $children[3]->getPropertyValue());
        $this->assertEquals('size', $children[4]->getPropertyKey());
        $this->assertEquals('40', $children[4]->getPropertyValue());
        $this->assertEquals('definitional', $children[5]->getPropertyKey());
        $this->assertEquals('true', $children[5]->getPropertyValue());
        $this->assertEquals('defaultOnly', $children[6]->getPropertyKey());
        $this->assertEquals('false', $children[6]->getPropertyValue());
    }
    
    public function testNewPluginConfig() {
        $xml = simplexml_load_string($this->xmlData());
        $plugin = new Plugin();
        $property = $this->importer->newPluginConfig($plugin, 'plugin_config_props', $xml->xpath('//entry[string[1]/text()="plugin_config_props"]/list[1]')[0]);
        $this->assertInstanceOf(PluginProperty::class, $property);
        $this->assertEquals(4, count($property->getChildren()));
    }
    
    public function testAddProperties() {
        $em = $this->getDoctrine();
        
        $xml = simplexml_load_string($this->xmlData());
        $plugin = new Plugin();
        $plugin->setName('test plugin');
        $plugin->setVersion(2);
        $plugin->setIdentifier('com.example.lockss.plugin');
        $em->persist($plugin);
        $this->importer->addProperties($plugin, $xml);
        $em->flush();
        $this->assertEquals(45, count($plugin->getPluginProperties()));
    }
    
    public function testBuildPlugin() {
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $this->importer->buildPlugin($xml);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->getDoctrine()->flush(); // make sure it was flushed to the db.
        $this->assertNotNull($plugin->getId()); 
    }
    
    /**
     * @expectedException Exception
     */
    public function testBuildPluginMissingId() {
        $xml = simplexml_load_string($this->xmlData());
        $node = $xml->xpath('//entry[string/text()="plugin_version"]')[0];
        unset($node[0]); // remove plugin_identifier.
        $this->importer->buildPlugin($xml);
    }
    
    /**
     * @expectedException Exception
     */
    public function testBuildPluginDuplicate() {
        $xml = simplexml_load_string($this->xmlData());
        $this->importer->buildPlugin($xml);
        $this->getDoctrine()->flush();
        $this->importer->buildPlugin($xml);
    }
    
    public function testImport() {
        $zipArchive = $this->getArchiveStub();
        $plugin = $this->importer->import($zipArchive);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertEquals('ca.sfu.lib.plugin.coppul.WestVaultPlugin', $plugin->getIdentifier());
    }
    
    public function manifestData() {
        return <<<'ENDMANIFEST'
Manifest-Version: 1.0
Ant-Version: Apache Ant 1.10.1
Created-By: 1.8.0_144-b01 (Oracle Corporation)

Name: ca/sfu/lib/plugin/coppul/WestVaultPlugin.xml
Lockss-Plugin: true
SHA-256-Digest: 1PNUJEn9tHPeDE3qiIgXCzvi6eblJayHNZK1M3YWq30=

ENDMANIFEST;
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
