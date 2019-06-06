<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Entity;

use AppBundle\DataFixtures\ORM\LoadPluginProperty;
use AppBundle\Services\PluginImporter;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of PluginPropertyTest
 */
class PluginTest extends BaseTestCase {
    
    protected function getFixtures() {
        return [
            LoadPluginProperty::class,
        ];
    }
    
    protected function setup() : void {
        parent::setUp();
    }
    
    public function testGetRootPluginProperties() {
        $plugin = $this->getReference('plugin.1');
        $properties = $plugin->getRootPluginProperties();
        $this->assertCount(5, $properties);
        $this->assertEquals('plugin_identifier', $properties[0]->getPropertyKey());
        $this->assertEquals('au_name', $properties[1]->getPropertyKey());
        $this->assertEquals('plugin_version', $properties[2]->getPropertyKey());
        $this->assertEquals('au_permission_url', $properties[3]->getPropertyKey());
        $this->assertEquals('plugin_config_props', $properties[4]->getPropertyKey());
    }
    
    /**
     * @dataProvider getPropertyData
     */
    public function testGetProperty($key, $value) {
        $plugin = $this->getReference('plugin.1');
        $property = $plugin->getProperty($key);
        $this->assertEquals($value, $property->getPropertyValue());
    }
    
    public function getPropertyData() {
        return [
            ['plugin_identifier', 'ca.example.lockss.plugin'],
            ['au_name', '"Dummy AU %d", container_number'],
            ['plugin_version', 301],
            ['au_permission_url', [
                '"%s", manifest_url',
                '"%s", permission_url'
            ]],
        ];
    }
    
    public function testGetPluginConfigParams() {
        $importer = $this->container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $definitionals = $plugin->getPluginConfigParams();
        $this->assertEquals(4, count($definitionals));
        $this->assertEquals('org.lockss.daemon.ConfigParamDescr', $definitionals[0]->getPropertyKey());
        $this->assertEquals('base_url', $definitionals[0]->getChildren()[0]->getPropertyValue());
        
        $this->assertEquals('org.lockss.daemon.ConfigParamDescr', $definitionals[1]->getPropertyKey());
        $this->assertEquals('container_number', $definitionals[1]->getChildren()[0]->getPropertyValue());
        
        $this->assertEquals('org.lockss.daemon.ConfigParamDescr', $definitionals[2]->getPropertyKey());
        $this->assertEquals('manifest_url', $definitionals[2]->getChildren()[0]->getPropertyValue());
        
        $this->assertEquals('org.lockss.daemon.ConfigParamDescr', $definitionals[3]->getPropertyKey());
        $this->assertEquals('permission_url', $definitionals[3]->getChildren()[0]->getPropertyValue());
    }
    
    public function testGetDefinitionalParams() {
        $importer = $this->container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $definitionals = $plugin->getDefinitionalPropertyNames();
        $this->assertEquals(2, count($definitionals));
        $this->assertEquals('base_url', $definitionals[0]);        
        $this->assertEquals('container_number', $definitionals[1]);
    }
    
    public function testGetNonDefinitionalParams() {
        $importer = $this->container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $nonDefinitionals = $plugin->getNonDefinitionalProperties();
        $this->assertEquals(2, count($nonDefinitionals));
        $this->assertEquals('manifest_url', $nonDefinitionals[0]);        
        $this->assertEquals('permission_url', $nonDefinitionals[1]);
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
    
}
