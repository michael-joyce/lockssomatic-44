<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadPln;
use AppBundle\Entity\Au;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Plugin;
use AppBundle\Services\ConfigExporter;
use AppBundle\Services\FilePaths;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Description of ConfigExporterTest
 *
 * @author michael
 */
class ConfigExporterTest extends BaseTestCase {

    /**
     * @var ConfigExporter
     */
    private $exporter;

    /**
     * @var vfsStreamDirectory
     */
    private $root;

    protected function setUp() {
        parent::setUp();
        $this->exporter = $this->container->get(ConfigExporter::class);

        $this->fp = $this->container->get(FilePaths::class);
        $this->root = vfsStream::setup('/confdir');
        $this->fp->setRoot($this->root->url());
        $this->exporter->setFilePaths($this->fp);
    }

    protected function getFixtures() {
        return [
            LoadPln::class,
        ];
    }

    public function testSanity() {
        $this->assertInstanceOf(ConfigExporter::class, $this->exporter);
    }

    public function testExportLockssXml() {
        $this->exporter->exportLockssXml($this->getReference('pln.1'));
        $this->assertTrue(file_exists("vfs://confdir/data/plnconfigs/1/properties/lockss.xml"));
    }

    public function testExportKeystore() {
        $pln = $this->getReference('pln.1');
        $url = vfsStream::url('confdir/foo.keystore');
        file_put_contents($url, "some keystore stuff.");
        $pln->setKeystore($url);
        $this->exporter->exportKeystore($pln);
        $this->assertTrue(file_exists("vfs://confdir/data/plnconfigs/1/plugins/lockss.keystore"));
    }

    public function testExportPlugins() {
        $url = vfsStream::url('confdir/plugin.jar');
        file_put_contents($url, "jar data");

        $plugin = new Plugin();
        $plugin->setPath($url);

        $provider = new ContentProvider();
        $provider->setPlugin($plugin);

        $pln = $this->getReference('pln.1');
        $pln->addContentProvider($provider);

        $this->exporter->exportPlugins($pln);
        $this->assertTrue(file_exists("vfs://confdir/data/plnconfigs/1/plugins/plugin.jar"));
        $this->assertTrue(file_exists("vfs://confdir/data/plnconfigs/1/plugins/index.html"));
    }

    public function testExportManifests() {
    }

}
