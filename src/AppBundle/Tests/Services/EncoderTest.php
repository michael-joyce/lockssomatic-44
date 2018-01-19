<?php

namespace AppBundle\Tests\Services;

use AppBundle\Services\Encoder;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class EncoderTest extends BaseTestCase {

    /**
     * @var Encoder
     */
    private $encoder;

    protected function setUp() {
        parent::setUp();
        $this->encoder = static::$kernel->getContainer()->get(Encoder::class);
    }

    public function testSanity() {
        $this->assertInstanceOf(Encoder::class, $this->encoder);
    }
    
    /**
     * @dataProvider encodeData
     * 
     * @param string $encoded
     * @param string $raw
     */
    public function testEncode($encoded, $raw) {
        $this->assertEquals($encoded, $this->encoder->encode($raw));
    }
    
    public function encodeData() {
        return [
            ['abc123', 'abc123'],
            ['abc-123', 'abc-123'],
            ['abc_123', 'abc_123'],
            ['abc*123', 'abc*123'],
            ['abc+123', 'abc 123'],
            
            ['abc%2E123', 'abc.123'],
            ['abc%2F123', 'abc/123'],
            ['abc%2B123', 'abc+123'],
            ['abc%3A123', 'abc:123'],
            ['abc%25123', 'abc%123'],
            ['abc%7E123', 'abc~123'],
        ];
    }

    /**
     * @dataProvider encodeData
     * 
     * @param string $encoded
     * @param string $raw
     */
    public function testDecode($encoded, $raw) {
        $this->assertEquals($raw, $this->encoder->decode($encoded));
    }
    
}
