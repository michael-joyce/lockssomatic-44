<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Utilities;

use App\Utilities\Encoder;
use PHPUnit\Framework\TestCase;

class EncoderTest extends TestCase {
    /**
     * @var Encoder
     */
    private $encoder;

    public function testSanity() : void {
        $this->assertInstanceOf(Encoder::class, $this->encoder);
    }

    /**
     * @dataProvider encodeData
     *
     * @param string $encoded
     * @param string $raw
     */
    public function testEncode($encoded, $raw) : void {
        $this->assertSame($encoded, $this->encoder->encode($raw));
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
            ['', ''],
        ];
    }

    /**
     * @dataProvider encodeData
     *
     * @param string $encoded
     * @param string $raw
     */
    public function testDecode($encoded, $raw) : void {
        $this->assertSame($raw, $this->encoder->decode($encoded));
    }

    protected function setup() : void {
        parent::setUp();
        $this->encoder = new Encoder();
    }
}
