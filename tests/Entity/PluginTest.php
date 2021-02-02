<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Entity;

use App\DataFixtures\PluginPropertyFixtures;
use App\Services\PluginImporter;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of PluginPropertyTest.
 */
class PluginTest extends ControllerBaseCase {
    protected function fixtures() : array {
        return [
            PluginPropertyFixtures::class,
        ];
    }

    public function testGetRootPluginProperties() : void {
        $plugin = $this->getReference('plugin.1');
        $properties = $plugin->getRootPluginProperties();
        $this->assertCount(5, $properties);
        $this->assertSame('plugin_identifier', $properties[0]->getPropertyKey());
        $this->assertSame('au_name', $properties[1]->getPropertyKey());
        $this->assertSame('plugin_version', $properties[2]->getPropertyKey());
        $this->assertSame('au_permission_url', $properties[3]->getPropertyKey());
        $this->assertSame('plugin_config_props', $properties[4]->getPropertyKey());
    }

    /**
     * @dataProvider getPropertyData
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function testGetProperty($key, $value) : void {
        $plugin = $this->getReference('plugin.1');
        $property = $plugin->getProperty($key);
        $this->assertSame($value, $property->getPropertyValue());
    }

    public function getPropertyData() {
        return [
            ['plugin_identifier', 'ca.example.lockss.plugin'],
            ['au_name', '"Dummy AU %d", container_number'],
            ['plugin_version', '301'],
            ['au_permission_url', [
                '"%s", manifest_url',
                '"%s", permission_url',
            ]],
        ];
    }

    public function testGetPluginConfigParams() : void {
        $importer = self::$container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $definitionals = $plugin->getPluginConfigParams();
        $this->assertCount(4, $definitionals);
        $this->assertSame('org.lockss.daemon.ConfigParamDescr', $definitionals[0]->getPropertyKey());
        $this->assertSame('base_url', $definitionals[0]->getChildren()[0]->getPropertyValue());

        $this->assertSame('org.lockss.daemon.ConfigParamDescr', $definitionals[1]->getPropertyKey());
        $this->assertSame('container_number', $definitionals[1]->getChildren()[0]->getPropertyValue());

        $this->assertSame('org.lockss.daemon.ConfigParamDescr', $definitionals[2]->getPropertyKey());
        $this->assertSame('manifest_url', $definitionals[2]->getChildren()[0]->getPropertyValue());

        $this->assertSame('org.lockss.daemon.ConfigParamDescr', $definitionals[3]->getPropertyKey());
        $this->assertSame('permission_url', $definitionals[3]->getChildren()[0]->getPropertyValue());
    }

    public function testGetDefinitionalParams() : void {
        $importer = self::$container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $definitionals = $plugin->getDefinitionalPropertyNames();
        $this->assertCount(2, $definitionals);
        $this->assertSame('base_url', $definitionals[0]);
        $this->assertSame('container_number', $definitionals[1]);
    }

    public function testGetNonDefinitionalParams() : void {
        $importer = self::$container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $nonDefinitionals = $plugin->getNonDefinitionalProperties();
        $this->assertCount(2, $nonDefinitionals);
        $this->assertSame('manifest_url', $nonDefinitionals[0]);
        $this->assertSame('permission_url', $nonDefinitionals[1]);
    }

    public function xmlData() {
        return <<<'ENDXML'
<map>
    <entry>
        <string>plugin_config_props</string>
        <list>
            <org.lockss.daemon.ConfigParamDescr>
                <key>base_url</key>
                <definitional>true</definitional>
            </org.lockss.daemon.ConfigParamDescr>
            <org.lockss.daemon.ConfigParamDescr>
                <key>container_number</key>
                <definitional>true</definitional>
            </org.lockss.daemon.ConfigParamDescr>
            <org.lockss.daemon.ConfigParamDescr>
                <key>manifest_url</key>
                <definitional>false</definitional>
            </org.lockss.daemon.ConfigParamDescr>
            <org.lockss.daemon.ConfigParamDescr>
                <key>permission_url</key>
                <definitional>false</definitional>
            </org.lockss.daemon.ConfigParamDescr>
        </list>
    </entry>
    <entry>
        <string>plugin_version</string>
        <string>1</string>
    </entry>
</map>
ENDXML;
    }

    protected function setup() : void {
        parent::setUp();
    }
}
