<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\Content;
use AppBundle\Entity\Plugin;
use AppBundle\Services\AuIdGenerator;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of AuIdGenerator
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuIdGeneratorTest extends BaseTestCase {

    /**
     * AuIdGenerator
     */
    private $generator;

    protected function setUp() {
        parent::setUp();
        $this->generator = $this->container->get(AuIdGenerator::class);
    }
    
    public function testInstance() {
        $this->assertInstanceOf(AuIdGenerator::class, $this->generator);
    }
    
    public function testFromContent() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalProperties')->will($this->returnValue([
            'foo', 'bar', 'bax'
        ]));
        $content = $this->createMock(Content::class);
        $content->method('getPlugin')->will($this->returnValue($plugin));
        $content->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $id = $this->generator->fromContent($content);
        $this->assertEquals('ca|example|plugin&bar~other%2Eproperty&bax~property+the+third%21&foo~Some+complex+title', $id);
    }

    public function testFromAu() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalProperties')->will($this->returnValue([
            'foo', 'bar', 'bax'
        ]));
        $content = $this->createMock(Content::class);
        $content->method('getPlugin')->will($this->returnValue($plugin));
        $content->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $au = new Au();
        $au->addContent($content);
        $id = $this->generator->fromAu($au);
        $this->assertEquals('ca|example|plugin&bar~other%2Eproperty&bax~property+the+third%21&foo~Some+complex+title', $id);
    }
    
}
