<?php

namespace AppBundle\Tests\Services;

use AppBundle\Services\SoapClientBuilder;
use BeSimple\SoapClient\SoapClient;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class SoapClientBuilderTest extends BaseTestCase {

    /**
     * @var SoapClientBuilder
     */
    private $builder;

    protected function setUp() {
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

    /**
     * @expectedException Exception
     */
    public function testBuildNotFound() {
        $client = $this->builder->build(dirname(__DIR__) . '/Data/dummy.notafile', array());
        $this->assertNotNull($client);
        $this->assertInstanceOf(SoapClient::class, $client);
    }

    /**
     * @expectedException Exception
     */
    public function testBuildNotXml() {
        $client = $this->builder->build(dirname(__DIR__) . '/Data/dummy.keystore', array());
        $this->assertNotNull($client);
        $this->assertInstanceOf(SoapClient::class, $client);
    }
}
