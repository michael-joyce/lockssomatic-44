<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\AuProperty;
use AppBundle\Entity\Plugin;
use AppBundle\Entity\PluginProperty;
use AppBundle\Services\AuPropertyGenerator;
use Exception;
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

    /**
     * @dataProvider generateStringData
     */
    public function testGenerateString($expected, $value, $map) {
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap($map));

        $this->assertEquals($expected, $this->generator->generateString($au, $value));
    }

    public function generateStringData() {
        return [
            // [expected, value, map]
            ['', '""', []],
            ['Some testing.', '"Some testing."', []],
            ['Some testing.', '"Some %s.", foo', [
                    ['foo', 'testing']
                ]],
            ['Test number 1 etc.', '"Test number %d etc.", bling', [
                    ['bling', 1]
                ]],
            ['Test number 1 etc.', '"Test number %d etc.", bling', [
                    ['bling', 1],
                    ['murphy', 1],
                    ['wilson', 1],
                ]],
            ['Test jango is number 1.', '"Test %s is number %d.", bling, murphy', [
                    ['bling', 'jango'],
                    ['murphy', 1],
                ]],
        ];
    }

    /**
     * @dataProvider generateStringBadData
     * @expectedException Exception
     */
    public function testGenerateBadString($expected, $value, $map) {
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap($map));

        $this->assertEquals($expected, $this->generator->generateString($au, $value));
    }

    public function generateStringBadData() {
        return [
            // [expected, value, map]
            ['', '', []],
            ['Some testing.', '"Some %s."', [
                    ['foo', 'testing']
                ]],
            ['Test number 1 etc.', '"Test number %d etc., bling', [
                    ['bling', 1]
                ]],
        ];
    }

    /**
     * @dataProvider testGenerateSymbolData
     */
    public function testGenerateSymbolString(array $data) {
        $property = $this->createMock(PluginProperty::class);
        $property->method('isList')->will($this->returnValue($data['list']));
        $property->method('getPropertyValue')->will($this->returnValue($data['propValue']));

        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getProperty')->will($this->returnValue($property));
        $plugin->method('getName')->will($this->returnValue('dummy'));

        $au = $this->createMock(Au::class);
        $au->method('getPlugin')->will($this->returnValue($plugin));
        $au->method('getAuPropertyValue')->will($this->returnValueMap($data['auValues']));

        $str = $this->generator->generateSymbol($au, 'testable');
        $this->assertEquals($data['expected'], $str);
    }

    public function testGenerateSymbolData() {
        return [
            [[
                'list' => false,
                'propValue' => '"item is a cheese"',
                'auValues' => [
                    ['fromage', 'cheddar'],
                ],
                'expected' => 'item is a cheese',
            ]],
            [[
                'list' => false,
                'propValue' => '"item %s is a cheese", fromage',
                'auValues' => [
                    ['fromage', 'cheddar'],
                ],
                'expected' => 'item cheddar is a cheese',
            ]],
            [[
                'list' => false,
                'propValue' => '"Sand is %s and %s", grit, color',
                'auValues' => [
                    ['fromage', 'cheddar'],
                    ['grit', 'coarse'],
                    ['color', 'black']
                ],
                'expected' => 'Sand is coarse and black',
            ]],
            
            [[
                'list' => true,
                'propValue' => [
                    '"item %s is a cheese", fromage',
                    '"Sand is %s and %s", grit, color',
                ],
                'auValues' => [
                    ['fromage', 'cheddar'],
                    ['grit', 'coarse'],
                    ['color', 'black']
                ],
                'expected' => [
                    'item cheddar is a cheese', 
                    'Sand is coarse and black'
                ],
            ]],
            
            [[
                'list' => true,
                'propValue' => [
                    '"item %s is a cheese", fromage',
                    '"Sand is %s and %s", fromage, fromage',
                ],
                'auValues' => [
                    ['fromage', 'cheddar'],
                    ['grit', 'coarse'],
                    ['color', 'black']
                ],
                'expected' => [
                    'item cheddar is a cheese', 
                    'Sand is cheddar and cheddar'
                ],
            ]],
        ];
    }

}
