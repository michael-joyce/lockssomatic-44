<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Utilities;

use AppBundle\Utilities\Namespaces;
use PHPUnit\Framework\TestCase;

/**
 * Simplify handling namespaces for SWORD XML documents.
 */
class NamespacesTest extends TestCase {

    /**
     * @dataProvider getNamespaceData
     */
    public function testGetNamespace($prefix, $expected) {
        $this->assertEquals($expected, Namespaces::getNamespace($prefix));
    }

    public function getNamespaceData() {
        return [
            ['dcterms', 'http://purl.org/dc/terms/'],
            ['sword', 'http://purl.org/net/sword/'],
            ['atom', 'http://www.w3.org/2005/Atom'],
            ['lom', 'http://lockssomatic.info/SWORD2'],
            ['rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'],
            ['app', 'http://www.w3.org/2007/app'],
        ];
    }
    
    public function testRegisterNamespaces() {
        $xml = simplexml_load_string($this->getXml());
        Namespaces::registerNamespaces($xml);
        $this->assertEquals(1, (string)$xml->xpath('//dcterms:a[1]/text()')[0]);
        $this->assertEquals(2, (string)$xml->xpath('//sword:b[1]/text()')[0]);
        $this->assertEquals(3, (string)$xml->xpath('//atom:c[1]/text()')[0]);
        $this->assertEquals(4, (string)$xml->xpath('//lom:d[1]/text()')[0]);
        $this->assertEquals(5, (string)$xml->xpath('//rdf:e[1]/text()')[0]);
        $this->assertEquals(6, (string)$xml->xpath('//app:f[1]/text()')[0]);
    }
    
    public function getXml() {
        return <<<"ENDXML"
        <root>
          <a xmlns="http://purl.org/dc/terms/">1</a>
          <b xmlns="http://purl.org/net/sword/">2</b>
          <c xmlns="http://www.w3.org/2005/Atom">3</c>
          <d xmlns="http://lockssomatic.info/SWORD2">4</d>
          <e xmlns="http://www.w3.org/1999/02/22-rdf-syntax-ns#">5</e>
          <f xmlns="http://www.w3.org/2007/app">6</f>
        </root>
ENDXML;
    }

}
