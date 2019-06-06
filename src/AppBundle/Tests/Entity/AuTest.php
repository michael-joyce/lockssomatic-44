<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\Au;
use AppBundle\Entity\AuProperty;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of PluginPropertyTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuTest extends BaseTestCase {

    /**
     * @var Au
     */
    private $au;
    
    protected function setup() : void {
        parent::setUp();
        $this->au = new Au();
    }
    
    public function testGetRootAuProperties() {
        $parent = new AuProperty();
        $child = new AuProperty();
        $parent->addChild($child);
        $child->setParent($parent);
        $this->au->addAuProperty($parent);
        $this->au->addAuProperty($child);
        $roots = $this->au->getRootAuProperties();
        $this->assertEquals(1, count($roots));
        $this->assertEquals($parent, $roots[0]);
    }
    
    public function testGetAuProperty() {
        $parent = new AuProperty();
        $this->au->addAuProperty($parent);
        
        $key = new AuProperty();
        $key->setParent($parent);
        $parent->addChild($key);
        $key->setPropertyKey('key');
        $key->setPropertyValue('foobar');
        $this->au->addAuProperty($key);
        
        $value = new AuProperty();
        $value->setParent($parent);
        $parent->addChild($value);
        $value->setPropertyKey('value');
        $value->setPropertyValue('cheese it.');
        $this->au->addAuProperty($value);

        $this->assertEquals('cheese it.', $this->au->getAuPropertyValue('foobar'));
    }
}
