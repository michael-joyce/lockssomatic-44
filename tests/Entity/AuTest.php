<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Entity;

use App\Entity\Au;
use App\Entity\AuProperty;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of PluginPropertyTest.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuTest extends ControllerBaseCase {
    /**
     * @var Au
     */
    private $au;

    public function testGetRootAuProperties() : void {
        $parent = new AuProperty();
        $child = new AuProperty();
        $parent->addChild($child);
        $child->setParent($parent);
        $this->au->addAuProperty($parent);
        $this->au->addAuProperty($child);
        $roots = $this->au->getRootAuProperties();
        $this->assertCount(1, $roots);
        $this->assertSame($parent, $roots[0]);
    }

    public function testGetAuProperty() : void {
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

        $this->assertSame('cheese it.', $this->au->getAuPropertyValue('foobar'));
    }

    protected function setup() : void {
        parent::setUp();
        $this->au = new Au();
    }
}
