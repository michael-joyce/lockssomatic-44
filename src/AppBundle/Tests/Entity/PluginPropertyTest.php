<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\PluginProperty;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use ReflectionObject;

/**
 * Description of PluginPropertyTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class PluginPropertyTest extends BaseTestCase {

    /**
     * @var PluginProperty
     */
    private $property;
    
    /**
     * Using reflection to look at private variables. Madness.
     * @var ReflectionObject
     */
    private $reflection;
    
    protected function setup() : void {
        parent::setUp();
        $this->property = new PluginProperty();
        $this->reflection = new ReflectionObject($this->property);
    }
    
    public function testSetPropertyValueString() {
        $this->property->setPropertyValue('fancypants');
        $this->assertFalse($this->property->isList());
        $internal = $this->reflection->getProperty('propertyValue');
        $internal->setAccessible(true);
        $this->assertEquals('fancypants', $internal->getValue($this->property));
    }
    
    public function testSetPropertyValueList() {
        $data = ['fancy', 'pants'];
        $this->property->setPropertyValue($data);
        $this->assertTrue($this->property->isList());
        $internal = $this->reflection->getProperty('propertyValue');
        $internal->setAccessible(true);
        $this->assertEquals(serialize($data), $internal->getValue($this->property));
    }
    
    public function testGetPropertyValueString() {
        $this->property->setPropertyValue('fancypants');
        $this->assertFalse($this->property->isList());
        $this->assertEquals('fancypants', $this->property->getPropertyValue());
    }
    
    public function testGetPropertyValueList() {
        $data = ['fancy', 'pants'];
        $this->property->setPropertyValue($data);
        $this->assertTrue($this->property->isList());
        $this->assertEquals($data, $this->property->getPropertyValue());
    }
}
