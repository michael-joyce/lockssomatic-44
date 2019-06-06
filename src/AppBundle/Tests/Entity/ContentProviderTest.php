<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\ContentProvider;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of PluginPropertyTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class ProviderTest extends BaseTestCase {

    /**
     * @var ContentProvider
     */
    private $provider;
    
    protected function setup() : void {
        parent::setUp();
        $this->provider = new ContentProvider();
    }

    /**
     * @dataProvider getPermissionHostData
     */
    public function testGetPermissionHost($expected, $url) {
        $this->provider->setPermissionUrl(
                $url);
        $this->assertEquals($expected, $this->provider->getPermissionHost());
    }
    
    public function getPermissionHostData() {
        return array(
            ['example.com', 'http://example.com/path/to/stuff'],
            ['www.example.com', 'http://www.example.com/path/to/stuff'],
            ['example.com', 'http://example.com/path/to/stuff?foo=bar'],
            ['www.example.com', 'http://www.example.com/path/to/stuff#fancy'],
            
            ['example.com', 'httpss://example.com/path/to/stuff'],
            ['www.example.com', 'https://www.example.com/path/to/stuff'],
            ['example.com', 'https://example.com/path/to/stuff?foo=bar'],
            ['www.example.com', 'https://www.example.com/path/to/stuff#fancy'],
        );
    }
    
}
