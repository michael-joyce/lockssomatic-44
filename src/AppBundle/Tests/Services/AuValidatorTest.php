<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\Content;
use AppBundle\Services\AuValidator;
use AppBundle\Services\PluginImporter;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of AuPropertyGeneratorTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuValidatorTest extends BaseTestCase {

    /**
     * @var AuValidator
     */
    private $validator;

    protected function getFixtures() {
        return [];
    }
    
    public function setUp() {
        parent::setUp();
        $this->validator = $this->container->get(AuValidator::class);
    }

    public function testSanity() {
        $this->assertInstanceOf(AuValidator::class, $this->validator);
    }

    public function testValidateSingle() {
        $importer = $this->container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $au = new Au();
        $au->setPlugin($plugin);
        $this->em->persist($au);
        
        $content = new Content();
        $content->setUrl("http://example.com/path/1");
        $content->setTitle("Item 1");
        $content->setDateDeposited(new \DateTime());
        $content->setProperty('base_url', 'http://example.com/path');
        $content->setProperty('container_number', 1);
        $content->setProperty('permission_url', "http://example.com/permission/1");
        $content->setAu($au);
        $this->em->persist($content);
        $this->em->flush();
        
        $this->assertEquals(0, $this->validator->validate($au));
    }

    public function testValidate() {
        $importer = $this->container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $au = new Au();
        $au->setPlugin($plugin);
        $this->em->persist($au);
        
        for($i = 0; $i < 10; $i++) {
            $content = new Content();
            $content->setUrl("http://example.com/path/{$i}");
            $content->setTitle("Item {$i}");
            $content->setDateDeposited(new \DateTime());
            $content->setProperty('base_url', 'http://example.com/path');
            $content->setProperty('container_number', 1);
            $content->setProperty('permission_url', "http://example.com/permission/{$i}");
            $content->setAu($au);
            $this->em->persist($content);
        }
        $this->em->flush();
        
        $this->assertEquals(0, $this->validator->validate($au));
    }

    public function testValidateFail() {
        $importer = $this->container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $au = new Au();
        $au->setPlugin($plugin);
        $this->em->persist($au);
        
        for($i = 0; $i < 10; $i++) {
            $content = new Content();
            $content->setUrl("http://example.com/path/{$i}");
            $content->setTitle("Item {$i}");
            $content->setDateDeposited(new \DateTime());
            $content->setProperty('base_url', "http://example.com/path/{$i}");
            $content->setProperty('container_number', 1);
            $content->setProperty('permission_url', "http://example.com/permission/{$i}");
            $content->setAu($au);
            $this->em->persist($content);
        }
        $this->em->flush();
        
        $this->assertEquals(9, $this->validator->validate($au));
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
        <string>2</string>
    </entry>
    <entry>
        <string>plugin_name</string>
        <string>Plugin</string>
    </entry>
    <entry>
        <string>plugin_identifier</string>
        <string>ca.sfu.lib.test.plugin</string>
    </entry>
</map>
ENDXML;
    }

}
