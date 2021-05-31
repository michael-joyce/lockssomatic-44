<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Utilities;

use App\Utilities\Hasher;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class HasherTest extends TestCase {
    /**
     * @var Hasher
     */
    private $hasher;

    /**
     * @var vfsStreamDirectory
     */
    private $root;

    public function testContainer() : void {
        $this->assertInstanceOf(Hasher::class, $this->hasher);
    }

    public function testHash() : void {
        $data = 'some junk.';
        $file = vfsStream::newFile('testfile')
            ->withContent($data)
            ->at($this->root)
        ;
        $this->assertSame(md5($data), $this->hasher->hash($file->url(), 'md5'));
        $this->assertSame(sha1($data), $this->hasher->hash($file->url(), 'sha1'));
    }

    protected function setup() : void {
        parent::setUp();
        $this->root = vfsStream::setup('/root');
        $this->hasher = new Hasher();
    }
}
