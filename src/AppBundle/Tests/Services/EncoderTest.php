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

}
