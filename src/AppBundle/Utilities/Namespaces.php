<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Utilities;

use SimpleXMLElement;

/**
 * Simplify handling namespaces for SWORD XML documents.
 */
class Namespaces {

    const NS = array(
        'dcterms' => 'http://purl.org/dc/terms/',
        'sword' => 'http://purl.org/net/sword/',
        'atom' => 'http://www.w3.org/2005/Atom',
        'lom' => 'http://lockssomatic.info/SWORD2',
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'app' => 'http://www.w3.org/2007/app',
    );
    
    /**
     * Get the FQDN for the prefix, in a case-insensitive fashion.
     *
     * @param string $prefix
     *   The usual namespace prefix to dereference.
     *
     * @return string
     *   The URI or null.
     */
    public static function getNamespace($prefix) {
        if (array_key_exists($prefix, self::NS)) {
            return self::NS[$prefix];
        }
        return null;
    }

    /**
     * Register all the known namespaces in a SimpleXMLElement.
     *
     * @param SimpleXMLElement $xml
     *   The document for which namespaces should be registered.
     */
    public static function registerNamespaces(SimpleXMLElement $xml) {
        foreach (array_keys(self::NS) as $key) {
            $xml->registerXPathNamespace($key, self::NS[$key]);
        }
    }

}
