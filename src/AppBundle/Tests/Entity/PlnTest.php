<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\Pln;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of PluginPropertyTest.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class PlnTest extends BaseTestCase {
    /**
     * @var Pln
     */
    private $pln;

    /**
     * @dataProvider setPropertyData
     *
     * @param mixed $expected
     * @param mixed $name
     * @param mixed $value
     */
    public function testSetProperty($expected, $name, $value) : void {
        $this->pln->setProperty($name, $value);
        $this->assertSame($expected, $this->pln->getProperty($name));
    }

    public function setPropertyData() {
        return [
            ['bar', 'foo', 'bar'],
            ['bar', 'foo', ['bar']],
            [['a', 'b'], 'foo', ['a', 'b']],
            [['foo'], 'org.lockss.titleDbs', 'foo'],
            [['foo', 'bar'], 'org.lockss.titleDbs', ['foo', 'bar']],
        ];
    }

    protected function setup() : void {
        parent::setUp();
        $this->pln = new Pln();
    }
}
