<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\Pln;
use AppBundle\Entity\AuProperty;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of PluginPropertyTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class PlnTest extends BaseTestCase {

    /**
     * @var Pln
     */
    private $pln;
    
    protected function setUp() {
        parent::setUp();
        $this->pln = new Pln();
    }

    /**
     * @dataProvider setPropertyData
     */
    public function testSetProperty($expected, $name, $value) {
        $this->pln->setProperty($name, $value);
        $this->assertEquals($expected, $this->pln->getProperty($name));
    }
    
    public function setPropertyData() {
        return array(
            ['bar', 'foo', 'bar'],
            ['bar', 'foo', ['bar']],
            [['a', 'b'], 'foo', ['a', 'b']],
            [['foo'], 'org.lockss.titleDbs', 'foo'],
            [['foo', 'bar'], 'org.lockss.titleDbs', ['foo', 'bar']],
        );
    }
    
}
