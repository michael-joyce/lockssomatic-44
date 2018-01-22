<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Tests\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\AuProperty;
use AppBundle\Services\AuPropertyGenerator;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of AuPropertyGeneratorTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuPropertyGeneratorTest extends BaseTestCase {
    
    /**
     * @var AuPropertyGenerator
     */
    private $generator;
    
    public function setUp() {
        parent::setUp();
        $this->generator = $this->container->get(AuPropertyGenerator::class);
    }
    
    public function testSanity() {
        $this->assertInstanceOf(AuPropertyGenerator::class, $this->generator);
    }
    
    public function testBuildProperty() {
        $au = new Au();
        $key = 'foobar';
        $value = 'some long complex value.';
        $property = $this->generator->buildProperty($au, $key, $value);
        $this->assertInstanceOf(AuProperty::class, $property);
        $this->assertEquals($key, $property->getPropertyKey());
        $this->assertEquals($value, $property->getPropertyValue());
        $this->assertEquals(1, $au->getAuProperties()->count());
    }
    
    public function testBuildPropetyChild() {
        $au = new Au();
        $key = 'foobar';
        $value = 'some long complex value.';
        $parent = new AuProperty();
        $property = $this->generator->buildProperty($au, $key, $value, $parent);
        $this->assertTrue($parent->hasChildren());
        $this->assertEquals($parent, $property->getParent());
        $this->assertEquals(1, $au->getAuProperties()->count()); // didn't add parent to AU.
    }
    
}
