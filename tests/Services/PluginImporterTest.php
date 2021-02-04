<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\Entity\Plugin;
use App\Entity\PluginProperty;
use App\Services\PluginImporter;
use Exception;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use SimpleXMLElement;
use ZipArchive;

/**
 * Description of PluginImporterTest.
 */
class PluginImporterTest extends ControllerBaseCase {
    /**
     * @var PluginImporter
     */
    private $importer;

    protected function fixtures() : array {
        return [];
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
        $stub->method('getFromName')->willReturnCallback(function ($name) use ($entries) {
            if (isset($entries[$name])) {
                return $entries[$name];
            }
        });

        return $stub;
    }

    public function testContainer() : void {
        $this->assertInstanceOf(PluginImporter::class, $this->importer);
    }

    public function testGetManifest() : void {
        $archive = $this->getArchiveStub();
        $manifest = $this->importer->getManifest($archive);
        $this->assertCount(2, $manifest);
    }

    public function testManifestWithSpaces() : void {
        $data = $this->manifestData();
        // This is legitimate and allowed at the end of a manifest.
        // But it also caused a lot of problems in parsing so test it.
        $data .= "\n\n ";
        $manifest = $this->importer->parseManifest($data);
        $this->assertCount(2, $manifest);
    }

    public function testManifestSectionCount() : void {
        $data = $this->manifestData();
        $manifest = $this->importer->parseManifest($data);
        $this->assertCount(2, $manifest);
    }

    public function testManifestFirstSection() : void {
        $data = $this->manifestData();
        $manifest = $this->importer->parseManifest($data);
        $this->assertSame([
            'manifest-version' => '1.0',
            'ant-version' => 'Apache Ant 1.10.1',
            'created-by' => '1.8.0_144-b01 (Oracle Corporation)',
        ], $manifest[0]);
    }

    public function testManifestSecondSection() : void {
        $data = $this->manifestData();
        $manifest = $this->importer->parseManifest($data);
        $this->assertSame([
            'name' => 'ca/sfu/lib/plugin/coppul/WestVaultPlugin.xml',
            'lockss-plugin' => 'true',
            'sha-256-digest' => '1PNUJEn9tHPeDE3qiIgXCzvi6eblJayHNZK1M3YWq30=',
        ], $manifest[1]);
    }

    public function testManifestBlankLines() : void {
        $data = $this->manifestData() . "\n\n\n";
        $manifest = $this->importer->parseManifest($data);
        $this->assertCount(2, $manifest);
    }

    public function testFindPluginEntries() : void {
        $data = $this->manifestData();
        $manifest = $this->importer->parseManifest($data);
        $entries = $this->importer->findPluginEntries($manifest);
        $this->assertSame(['ca/sfu/lib/plugin/coppul/WestVaultPlugin.xml'], $entries);
    }

    public function testFindPluginXml() : void {
        $stub = $this->getArchiveStub();
        $xml = $this->importer->getPluginXml($stub, 'ca/sfu/lib/plugin/coppul/WestVaultPlugin.xml');
        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
    }

    public function testFindXmlPropString() : void {
        $xml = simplexml_load_string($this->xmlData());
        $this->assertSame('COPPUL WestVault Plugin', $this->importer->findXmlPropString($xml, 'plugin_name'));
        $this->assertNull($this->importer->findXmlPropString($xml, 'fancy_dan'));
    }

    public function testFindXmlPropStringException() : void {
        $this->expectException(Exception::class);
        $xml = simplexml_load_string($this->xmlData());
        $this->importer->findXmlPropString($xml, 'bad_entry');
    }

    public function testFindXmlPropElement() : void {
        $xml = simplexml_load_string($this->xmlData());
        $this->assertInstanceOf(SimpleXMLElement::class, $this->importer->findXmlPropElement($xml, 'au_permission_url'));
        $this->assertNull($this->importer->findXmlPropElement($xml, 'fancy_dan'));
    }

    public function testFindXmlPropElementException() : void {
        $this->expectException(Exception::class);
        $xml = simplexml_load_string($this->xmlData());
        $this->importer->findXmlPropElement($xml, 'other_bad_entry');
    }

    public function testNewPluginPropertyString() : void {
        $xml = simplexml_load_string($this->xmlData());
        $plugin = new Plugin();
        $property = $this->importer->newPluginProperty($plugin, 'plugin_version', $xml->xpath('//entry[string[1]/text()="plugin_version"]/string[2]')[0]);
        $this->assertInstanceOf(PluginProperty::class, $property);
        $this->assertSame('plugin_version', $property->getPropertyKey());
        $this->assertSame($plugin, $property->getPlugin());
        $this->assertSame('1', $property->getPropertyValue());
        $this->assertNull($property->getChildren());
        $this->assertFalse($property->isList());
    }

    public function testNewPluginPropertyList() : void {
        $xml = simplexml_load_string($this->xmlData());
        $plugin = new Plugin();
        $property = $this->importer->newPluginProperty($plugin, 'au_permission_url', $xml->xpath('//entry[string[1]/text()="au_permission_url"]/list[1]')[0]);
        $this->assertInstanceOf(PluginProperty::class, $property);
        $this->assertSame('au_permission_url', $property->getPropertyKey());
        $this->assertSame($plugin, $property->getPlugin());
        $this->assertSame(['"%s", manifest_url', '"%s", permission_url'], $property->getPropertyValue());
        $this->assertNull($property->getChildren());
        $this->assertTrue($property->isList());
    }

    public function testImportChildren() : void {
        $xml = simplexml_load_string($this->xmlData());
        $property = new PluginProperty();

        $plugin = new Plugin();
        $property->setPlugin($plugin);
        $plugin->addPluginProperty($property);

        $childProp = $this->importer->importChildren($property, $xml->xpath('//org.lockss.daemon.ConfigParamDescr[1]')[0]);
        $this->assertInstanceOf(PluginProperty::class, $childProp);
        $this->assertSame($plugin, $childProp->getPlugin());
        $this->assertSame($property, $childProp->getParent());

        $children = $childProp->getChildren();
        $this->assertCount(7, $children);
        $this->assertSame('key', $children[0]->getPropertyKey());
        $this->assertSame('base_url', $children[0]->getPropertyValue());
        $this->assertSame('displayName', $children[1]->getPropertyKey());
        $this->assertSame('Base URL', $children[1]->getPropertyValue());
        $this->assertSame('description', $children[2]->getPropertyKey());
        $this->assertSame('Usually of the form http://<journal-name>.com/', $children[2]->getPropertyValue());
        $this->assertSame('type', $children[3]->getPropertyKey());
        $this->assertSame('3', $children[3]->getPropertyValue());
        $this->assertSame('size', $children[4]->getPropertyKey());
        $this->assertSame('40', $children[4]->getPropertyValue());
        $this->assertSame('definitional', $children[5]->getPropertyKey());
        $this->assertSame('true', $children[5]->getPropertyValue());
        $this->assertSame('defaultOnly', $children[6]->getPropertyKey());
        $this->assertSame('false', $children[6]->getPropertyValue());
    }

    public function testNewPluginConfig() : void {
        $xml = simplexml_load_string($this->xmlData());
        $plugin = new Plugin();
        $property = $this->importer->newPluginConfig($plugin, 'plugin_config_props', $xml->xpath('//entry[string[1]/text()="plugin_config_props"]/list[1]')[0]);
        $this->assertInstanceOf(PluginProperty::class, $property);
        $this->assertCount(4, $property->getChildren());
    }

    public function testAddProperties() : void {
        $xml = simplexml_load_string($this->xmlData());
        $plugin = new Plugin();
        $plugin->setName('test plugin');
        $plugin->setVersion(2);
        $plugin->setIdentifier('com.example.lockss.plugin');
        $this->entityManager->persist($plugin);
        $this->importer->addProperties($plugin, $xml);
        $this->entityManager->flush();
        $this->assertCount(45, $plugin->getPluginProperties());
    }

    public function testBuildPlugin() : void {
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $this->importer->buildPlugin($xml);
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    public function testBuildPluginMissingId() : void {
        $this->expectException(Exception::class);
        $xml = simplexml_load_string($this->xmlData());
        $node = $xml->xpath('//entry[string/text()="plugin_version"]')[0];
        unset($node[0]); // remove plugin_identifier.
        $this->importer->buildPlugin($xml);
    }

    public function testBuildPluginDuplicate() : void {
        $this->expectException(Exception::class);
        $xml = simplexml_load_string($this->xmlData());
        $this->importer->buildPlugin($xml);
        $this->entityManager->flush();
        $this->importer->buildPlugin($xml);
    }

    public function testImport() : void {
        $zipArchive = $this->getArchiveStub();
        $plugin = $this->importer->import($zipArchive);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertSame('ca.sfu.lib.plugin.coppul.WestVaultPlugin', $plugin->getIdentifier());
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

    protected function setup() : void {
        parent::setUp();
        $this->importer = self::$container->get(PluginImporter::class);
    }
}
