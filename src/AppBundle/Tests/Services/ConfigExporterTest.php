<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadPln;
use AppBundle\Entity\Au;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Plugin;
use AppBundle\Services\AuManager;
use AppBundle\Services\ConfigExporter;
use AppBundle\Services\FilePaths;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Description of ConfigExporterTest.
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

    protected function getFixtures() {
        return [
            LoadPln::class,
        ];
    }

    public function testSanity() : void {
        $this->assertInstanceOf(ConfigExporter::class, $this->exporter);
    }

    public function testExportLockssXml() : void {
        $this->exporter->exportLockssXml($this->getReference('pln.1'));
        $this->assertTrue(file_exists('vfs://confdir/data/plnconfigs/1/properties/lockss.xml'));
    }

    public function testExportKeystore() : void {
        $pln = $this->getReference('pln.1');
        $url = vfsStream::url('confdir/foo.keystore');
        file_put_contents($url, 'some keystore stuff.');
        $pln->setKeystore($url);
        $this->exporter->exportKeystore($pln);
        $this->assertTrue(file_exists('vfs://confdir/data/plnconfigs/1/plugins/lockss.keystore'));
    }

    public function testExportNullKeystore() : void {
        $pln = $this->getReference('pln.1');
        $pln->setKeystore(null);
        $url = vfsStream::url('confdir/foo.keystore');
        file_put_contents($url, 'some keystore stuff.');
        $this->exporter->exportKeystore($pln);
        $this->assertFalse(file_exists('vfs://confdir/data/plnconfigs/1/plugins/lockss.keystore'));
    }

    public function testExportPlugins() : void {
        $url = vfsStream::url('confdir/plugin.jar');
        file_put_contents($url, 'jar data');

        $plugin = new Plugin();
        $plugin->setPath($url);

        $provider = new ContentProvider();
        $provider->setPlugin($plugin);

        $pln = $this->getReference('pln.1');
        $pln->addContentProvider($provider);

        $this->exporter->exportPlugins($pln);
        $this->assertTrue(file_exists('vfs://confdir/data/plnconfigs/1/plugins/plugin.jar'));
        $this->assertTrue(file_exists('vfs://confdir/data/plnconfigs/1/plugins/index.html'));
    }

    public function testExportMissingPlugins() : void {
        $this->expectException(Exception::class);
        $url = vfsStream::url('confdir/plugin.jar');

        $plugin = new Plugin();
        $plugin->setPath($url);

        $provider = new ContentProvider();
        $provider->setPlugin($plugin);

        $pln = $this->getReference('pln.1');
        $pln->addContentProvider($provider);

        $this->exporter->exportPlugins($pln);
    }

    public function testExportManifests() : void {
        $deposit = new Deposit();
        $deposit->setUrl('http://example.com/path/to/content');

        $manager = $this->createMock(AuManager::class);
        $manager->method('auDeposits')->willReturn([$deposit]);
        $this->exporter->setAuManager($manager);

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getManifestPath')->willReturn('vfs://confdir/manifest.html');
        $this->exporter->setFilePaths($fp);

        $pln = $this->getReference('pln.1');
        $au = new Au();
        $pln->addAu($au);
        $au->setPln($pln);
        $this->exporter->exportManifests($pln);

        $this->assertTrue(file_exists('vfs://confdir/manifest.html'));
    }

    public function testExportTitleDbs() : void {
        $fp = $this->createMock(FilePaths::class);
        $fp->method('getTitleDbPath')->willReturn('vfs://confdir/titledb.xml');
        $this->exporter->setFilePaths($fp);

        $au = new Au();
        $provider = new ContentProvider();
        $aus = new ArrayCollection();
        $aus[] = $au;
        $provider->setAus($aus);
        $au->setContentProvider($provider);

        $pln = $this->getReference('pln.1');
        $pln->addContentProvider($provider);

        $this->exporter->exportTitleDbs($pln);
        $this->assertTrue(file_exists('vfs://confdir/titledb.xml'));
    }

    protected function setup() : void {
        parent::setUp();
        $this->exporter = $this->container->get(ConfigExporter::class);

        $this->fp = $this->container->get(FilePaths::class);
        $this->root = vfsStream::setup('/confdir');
        $this->fp->setRoot($this->root->url());
        $this->exporter->setFilePaths($this->fp);
    }
}
