<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\DataFixtures\AuFixtures;
use App\DataFixtures\ContentProviderFixtures;
use App\DataFixtures\DepositFixtures;
use App\DataFixtures\PlnFixtures;
use App\DataFixtures\PluginFixtures;
use App\Services\FilePaths;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class FilePathsTest extends ControllerBaseCase {
    /**
     * @var FilePaths
     */
    private $filePaths;

    /**
     * @var string
     */
    private $root;

    protected function fixtures() : array {
        return [
            DepositFixtures::class,
            PlnFixtures::class,
            PluginFixtures::class,
            ContentProviderFixtures::class,
            AuFixtures::class,
        ];
    }

    public function testSanity() : void {
        $this->assertInstanceOf(FilePaths::class, $this->filePaths);
    }

    public function testGetRoot() : void {
        $this->assertSame($this->root, $this->filePaths->getRootPath());
    }

    public function testGetCacheDownloadDir() : void {
        $this->assertStringStartsWith($this->root, $this->filePaths->getCacheDownloadDir());
    }

    public function testGetDownloadContentPath() : void {
        $deposit = $this->getReference('deposit.1');
        $this->assertSame($this->root . '/data/download/3E40ACE2-7F1A-4AD5-8622-416EC740D9A1/1/first', $this->filePaths->getDownloadContentPath($deposit));
    }

    public function testGetLockssDir() : void {
        $this->assertSame($this->root . '/data/lockss', $this->filePaths->getLockssDir());
    }

    public function testGetPluginsDir() : void {
        $this->assertSame($this->root . '/data/lockss/plugins', $this->filePaths->getPluginsDir());
    }

    public function testGetLockssKeystoreDir() : void {
        $pln = $this->getReference('pln.1');
        $this->assertSame($this->root . '/data/lockss/keystores/1', $this->filePaths->getLockssKeystoreDir($pln));
    }

    public function testGetConfigsDir() : void {
        $pln = $this->getReference('pln.1');
        $this->assertSame($this->root . '/data/plnconfigs/1', $this->filePaths->getConfigsDir($pln));
    }

    public function testGetLockssXmlFile() : void {
        $pln = $this->getReference('pln.1');
        $this->assertSame($this->root . '/data/plnconfigs/1/properties/lockss.xml', $this->filePaths->getLockssXmlFile($pln));
    }

    public function testGetPluginsExportDir() : void {
        $pln = $this->getReference('pln.1');
        $this->assertSame($this->root . '/data/plnconfigs/1/plugins', $this->filePaths->getPluginsExportDir($pln));
    }

    public function testGetPluginsExportFile() : void {
        $pln = $this->getReference('pln.1');
        $plugin = $this->getReference('plugin.1');
        $this->assertSame(
            $this->root . '/data/plnconfigs/1/plugins/plugin1.jar',
            $this->filePaths->getPluginsExportFile($pln, $plugin)
        );
    }

    public function getPluginsManifestFile() : void {
        $pln = $this->getReference('pln.1');
        $this->assertSame(
            $this->root . '/data/plnconfigs/1/plugins/index.html',
            $this->filePaths->getPluginsManifestFile($pln)
        );
    }

    public function testGetManifestDir() : void {
        $pln = $this->getReference('pln.1');
        $provider = $this->getReference('provider.1');
        $this->assertSame(
            $this->root . '/data/plnconfigs/1/manifests/1/1',
            $this->filePaths->getManifestDir($pln, $provider)
        );
    }

    public function testGetManifestPath() : void {
        $au = $this->getReference('au.1');
        $this->assertSame(
            $this->root . '/data/plnconfigs/1/manifests/1/1/manifest_1.html',
            $this->filePaths->getManifestPath($au)
        );
    }

    public function testGetTitleDbDir() : void {
        $pln = $this->getReference('pln.1');
        $provider = $this->getReference('provider.1');
        $this->assertSame(
            $this->root . '/data/plnconfigs/1/titledbs/1/1',
            $this->filePaths->getTitleDbDir($pln, $provider)
        );
    }

    public function testGetTitleDbPath() : void {
        $pln = $this->getReference('pln.1');
        $provider = $this->getReference('provider.1');
        $this->assertSame(
            $this->root . '/data/plnconfigs/1/titledbs/1/1/titledb_3.xml',
            $this->filePaths->getTitleDbPath($provider, 3)
        );
    }

    protected function setup() : void {
        parent::setUp();
        $this->filePaths = self::$container->get(FilePaths::class);
        $this->root = self::$container->getParameter('kernel.project_dir');
    }
}
