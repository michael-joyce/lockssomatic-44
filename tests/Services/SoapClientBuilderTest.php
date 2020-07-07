<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Tests\Services;

use AppBundle\Services\SoapClientBuilder;
use BeSimple\SoapClient\SoapClient;
use Exception;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class SoapClientBuilderTest extends BaseTestCase {
    /**
     * @var SoapClientBuilder
     */
    private $builder;

    public function testSanity() : void {
        $this->assertInstanceOf(SoapClientBuilder::class, $this->builder);
    }

    public function testBuild() : void {
        $client = $this->builder->build(dirname(__DIR__) . '/Data/dummy.wsdl', []);
        $this->assertNotNull($client);
        $this->assertInstanceOf(SoapClient::class, $client);
    }

    public function testBuildNotFound() : void {
        $this->expectException(Exception::class);
        $client = $this->builder->build(dirname(__DIR__) . '/Data/dummy.notafile', []);
    }

    public function testBuildNotXml() : void {
        $this->expectException(Exception::class);
        $client = $this->builder->build(dirname(__DIR__) . '/Data/dummy.keystore', []);
    }

    protected function setup() : void {
        parent::setUp();
        $this->builder = $this->getContainer()->get(SoapClientBuilder::class);
    }
}
