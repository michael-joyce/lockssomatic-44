<?php

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\Content;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use ReflectionObject;

/**
 * Description of PluginPropertyTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class ContentTest extends BaseTestCase {

    /**
     * @var Content
     */
    private $content;
    
    /**
     * Using reflection to look at private variables. Madness.
     * @var ReflectionObject
     */
    private $reflection;
    
    protected function setUp() {
        parent::setUp();
        $this->content = new Content();
        $this->reflection = new ReflectionObject($this->content);
    }
    
    public function testSetChecksumValue() {
        $this->content->setChecksumValue('abc123');
        $internal = $this->reflection->getProperty('checksumValue');
        $internal->setAccessible(true);
        $this->assertEquals('ABC123', $internal->getValue($this->content));
    }
    
    /**
     * @dataProvider getPropertyValueData
     */
    public function testGetPropertyValue($expected, $value, $encoded) {
        $content = new Content();
        $content->setProperty('test', $value);
        $this->assertEquals($expected, $content->getProperty('test', $encoded));
    }
    
    public function getPropertyValueData() {
        return [
            ['abc', 'abc', false],
            ['abc def', 'abc def', false],
            ['abc%20def', 'abc def', true],
            ['abc%7Edef', 'abc~def', true],
            ['abc%2Fdef', 'abc/def', true],
            ['abc%2Edef', 'abc.def', true],
        ];
    }
    
}
