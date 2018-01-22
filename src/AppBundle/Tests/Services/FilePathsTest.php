<?php

namespace AppBundle\Tests\Services;

use AppBundle\Services\FilePaths;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class FilePathsTest extends BaseTestCase {

    /**
     * @var FilePaths
     */
    private $filePaths;
    
    /**
     * @var string
     */
    private $root;

    protected function setUp() {
        parent::setUp();
        self::bootKernel();
        $this->filePaths = $this->container->get(FilePaths::class);
        $this->root = $this->container->getParameter('kernel.project_dir');
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
