<?php

namespace AppBundle\Tests\Services;

use AppBundle\Services\FilePaths;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FilePathsTest extends KernelTestCase {

    /**
     * @var FilePaths
     */
    private $filePaths;
    private $root;

    public function setUp() {
        parent::setUp();
        self::bootKernel();
        $this->filePaths = static::$kernel->getContainer()->get('AppBundle\Services\FilePaths');
        $this->root = static::$kernel->getContainer()->getParameter('kernel.project_dir');
    }

    public function testSanity() {
        $this->assertInstanceOf(FilePaths::class, $this->filePaths);
    }

    public function testGetRoot() {
        $this->assertEquals($this->root, $this->filePaths->getRootPath());
    }

    public function testGetCacheDownloadDir() {
        $this->assertStringStartsWith($this->root, $this->filePaths->getCacheDownloadDir());
    }

    public function testGetLockssDir() {
        $this->assertEquals($this->root . "/data/lockss", $this->filePaths->getLockssDir());
    }

    public function testGetPluginsDir() {
        $this->assertEquals($this->root . "/data/lockss/plugins", $this->filePaths->getPluginsDir());
    }

}
