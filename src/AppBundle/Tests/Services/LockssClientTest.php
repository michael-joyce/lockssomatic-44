<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2018-06-06
 * Time: 4:54 PM
 */

namespace AppBundle\Tests\Services;

use AppBundle\Services\LockssClient;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class LockssClientTest extends BaseTestCase {

    /**
     * @var LockssClient
     */
    private $client;

    protected function setUp()
    {
        parent::setUp();
        $this->client = $this->getContainer()->get(LockssClient::class);
    }

    public function testSanity() {
        $this->assertInstanceOf(LockssClient::Class, $this->client);
    }

    public function testErrorHandler() {
        $this->client->errorHandler(1, "error string", __file__, __line__);
        $this->assertTrue($this->client->hasErrors());
        $errors = $this->client->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals("Error:error string", $errors[0]);
        $this->client->clearErrors();
        $this->assertFalse($this->client->hasErrors());
        $this->assertCount(0, $this->client->getErrors());
    }

    public function testExceptionHandler(){
        $exception = new \Exception("hi hi", 23);
        $this->client->exceptionHandler($exception);

        $this->assertTrue($this->client->hasErrors());
        $errors = $this->client->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals("Exception:23:hi hi", $errors[0]);
        $this->client->clearErrors();
        $this->assertFalse($this->client->hasErrors());
        $this->assertCount(0, $this->client->getErrors());
    }

}