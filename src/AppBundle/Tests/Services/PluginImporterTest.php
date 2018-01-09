<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Tests\Services;

use AppBundle\Services\PluginImporter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Description of PluginImporterTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class PluginImporterTest extends KernelTestCase {
    
    /**
     * @var PluginImporter
     */
    private $importer;
    
    protected function setUp() {
        parent::setUp();
        self::bootKernel();
        $this->importer = static::$kernel->getContainer()->get(PluginImporter::class);
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
            'Manifest-Version' => '1.0',
            'Ant-Version' => 'Apache Ant 1.10.1',
            'Created-By' => '1.8.0_144-b01 (Oracle Corporation)',
        ), $manifest[0]);
    }
    
    public function testManifestSecondSection() {
        $data = $this->manifestData();
        $manifest = $this->importer->parseManifest($data);
        $this->assertEquals(array(
            'Name' => 'ca/sfu/lib/plugin/coppul/WestVaultPlugin.xml',
            'Lockss-Plugin' => 'true',
            'SHA-256-Digest' => '1PNUJEn9tHPeDE3qiIgXCzvi6eblJayHNZK1M3YWq30=',
        ), $manifest[1]);
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
    
}
