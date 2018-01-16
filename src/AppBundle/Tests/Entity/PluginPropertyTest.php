<?php

namespace AppBundle\Tests\Entity\Dummy;

use AppBundle\Entity\PluginProperty;

class PluginPropertyDummy extends PluginProperty {
    public function getRawValue() {
        return $this->propertyValue;
    }
}

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\PluginProperty;
use AppBundle\Tests\Entity\Dummy\PluginPropertyDummy;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

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
    
    protected function setUp() {
        parent::setUp();
        $this->property = new PluginPropertyDummy();
    }
    
    public function testSetPropertyValueString() {
        $this->property->setPropertyValue('fancypants');
        $this->assertFalse($this->property->isList());
        $this->assertEquals('fancypants', $this->property->getRawValue());
    }
    
    public function testSetPropertyValueList() {
        $data = ['fancy', 'pants'];
        $this->property->setPropertyValue($data);
        $this->assertTrue($this->property->isList());
        $this->assertEquals(serialize($data), $this->property->getRawValue());
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
