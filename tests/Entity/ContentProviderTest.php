<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Entity;

use App\Entity\ContentProvider;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of PluginPropertyTest.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class ProviderTest extends ControllerBaseCase {
    /**
     * @var ContentProvider
     */
    private $provider;

    /**
     * @dataProvider getPermissionHostData
     *
     * @param mixed $expected
     * @param mixed $url
     */
    public function testGetPermissionHost($expected, $url) : void {
        $this->provider->setPermissionUrl(
            $url
        );
        $this->assertSame($expected, $this->provider->getPermissionHost());
    }

    public function getPermissionHostData() {
        return [
            ['example.com', 'http://example.com/path/to/stuff'],
            ['www.example.com', 'http://www.example.com/path/to/stuff'],
            ['example.com', 'http://example.com/path/to/stuff?foo=bar'],
            ['www.example.com', 'http://www.example.com/path/to/stuff#fancy'],

            ['example.com', 'httpss://example.com/path/to/stuff'],
            ['www.example.com', 'https://www.example.com/path/to/stuff'],
            ['example.com', 'https://example.com/path/to/stuff?foo=bar'],
            ['www.example.com', 'https://www.example.com/path/to/stuff#fancy'],
        ];
    }

    protected function setup() : void {
        parent::setUp();
        $this->provider = new ContentProvider();
    }
}
