<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Utilities;

use AppBundle\Utilities\Encoder;
use PHPUnit\Framework\TestCase;

class EncoderTest extends TestCase {

    /**
     * @var Encoder
     */
    private $encoder;

    protected function setUp() {
        parent::setUp();
        $this->encoder = new Encoder();
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
            ['abc%26123', 'abc&123'],
            ['abc%7C123', 'abc|123'],
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
