<?php

namespace AppBundle\Tests\Services;

use AppBundle\Services\SoapClientBuilder;
use BeSimple\SoapClient\SoapClient;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use Exception;

class SoapClientBuilderTest extends BaseTestCase {

    /**
     * @var SoapClientBuilder
     */
    private $builder;

    protected function setup() : void {
        parent::setUp();
        $this->builder = $this->getContainer()->get(SoapClientBuilder::class);
    }

    public function testSanity() {
        $this->assertInstanceOf(SoapClientBuilder::class, $this->builder);
    }

    public function testBuild() {
        $client = $this->builder->build(dirname(__DIR__) . '/Data/dummy.wsdl', array());
        $this->assertNotNull($client);
        $this->assertInstanceOf(SoapClient::class, $client);
    }

    public function testBuildNotFound() {
        $this->expectException(Exception::class);
        $client = $this->builder->build(dirname(__DIR__) . '/Data/dummy.notafile', array());
    }

    public function testBuildNotXml() {
        $this->expectException(Exception::class);
        $client = $this->builder->build(dirname(__DIR__) . '/Data/dummy.keystore', array());
    }
}
