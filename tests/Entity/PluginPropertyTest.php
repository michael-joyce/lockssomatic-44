<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Entity;

use App\Entity\PluginProperty;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use ReflectionObject;

/**
 * Description of PluginPropertyTest.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class PluginPropertyTest extends ControllerBaseCase {
    /**
     * @var PluginProperty
     */
    private $property;

    /**
     * Using reflection to look at private variables. Madness.
     *
     * @var ReflectionObject
     */
    private $reflection;

    public function testSetPropertyValueString() : void {
        $this->property->setPropertyValue('fancypants');
        $this->assertFalse($this->property->isList());
        $internal = $this->reflection->getProperty('propertyValue');
        $internal->setAccessible(true);
        $this->assertSame('fancypants', $internal->getValue($this->property));
    }

    public function testSetPropertyValueList() : void {
        $data = ['fancy', 'pants'];
        $this->property->setPropertyValue($data);
        $this->assertTrue($this->property->isList());
        $internal = $this->reflection->getProperty('propertyValue');
        $internal->setAccessible(true);
        $this->assertSame(serialize($data), $internal->getValue($this->property));
    }

    public function testGetPropertyValueString() : void {
        $this->property->setPropertyValue('fancypants');
        $this->assertFalse($this->property->isList());
        $this->assertSame('fancypants', $this->property->getPropertyValue());
    }

    public function testGetPropertyValueList() : void {
        $data = ['fancy', 'pants'];
        $this->property->setPropertyValue($data);
        $this->assertTrue($this->property->isList());
        $this->assertSame($data, $this->property->getPropertyValue());
    }

    protected function setup() : void {
        parent::setUp();
        $this->property = new PluginProperty();
        $this->reflection = new ReflectionObject($this->property);
    }
}
