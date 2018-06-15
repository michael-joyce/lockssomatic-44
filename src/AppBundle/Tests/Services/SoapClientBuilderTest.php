<?php

use BeSimple\SoapClient\SoapClient;
use BeSimple\SoapClient\SoapClientBuilder;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Tests\Services;

/**
 * Description of SoapClientBuilderTest
 *
 * @author mjoyce
 */
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

}
