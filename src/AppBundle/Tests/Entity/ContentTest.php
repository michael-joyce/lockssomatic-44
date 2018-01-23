<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\Content;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use ReflectionObject;

/**
 * Description of PluginPropertyTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class ContentTest extends BaseTestCase {

    /**
     * @var Content
     */
    private $content;
    
    /**
     * Using reflection to look at private variables. Madness.
     * @var ReflectionObject
     */
    private $reflection;
    
    protected function setUp() {
        parent::setUp();
        $this->content = new Content();
        $this->reflection = new ReflectionObject($this->content);
    }
    
    public function testSetChecksumValue() {
        $this->content->setChecksumValue('abc123');
        $internal = $this->reflection->getProperty('checksumValue');
        $internal->setAccessible(true);
        $this->assertEquals('ABC123', $internal->getValue($this->content));
    }
    
}
