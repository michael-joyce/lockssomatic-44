<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Plugin;
use AppBundle\Entity\PluginProperty;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SimpleXMLElement;
use ZipArchive;

/**
 * Description of PluginImporter
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class PluginImporter {
    
    const MANIFEST = 'META-INF/MANIFEST.MF';

    const PLUGIN_KEY = 'lockss-plugin';
    
    /**
     * Names of the prop strings in the plugin's XML configuration file which
     * should be imported.
     *
     * @var array
     */
    const PROP_STRINGS = array(
        'au_name',
        'au_permission_url',
        'plugin_crawl_type',
        'plugin_identifier',
        'plugin_name',
        'plugin_publishing_platform',
        'plugin_status',
        'plugin_version',
        'plugin_parent',
        'required_daemon_version',
    );

    /**
     * List of the property lists which should be imported.
     *
     * @var array
     */
    const PROP_LISTS = array(
        'au_crawlrules',
        'au_start_url',
    );
    
    const PROP_OBS = array(
        'plugin_config_props',
    );
    
    private $em;
    
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }
    
    public function import(Plugin $plugin, ZipArchive $reader = null) {
        $zipArchive = $reader;
        if($zipArchive === null) {
            $zipArchive = new ZipArchive();
        }
        $res = $zipArchive->open($plugin->getJarFile()->getPathname());
        if($res !== true) {
            throw new Exception("Cannot read plugin jar file. Error code {$res}.");
        }
        $raw = $zipArchive->getFromName(self::MANIFEST);
        $manifest = $this->parseManifest(preg_replace('/\r\n/', "\n", $raw));
        $entries = $this->findPluginEntries($manifest);
        foreach($entries as $entry) {
            $xml = $this->getPluginXml($zipArchive, $entry);
        }
    }
    
    public function getManifest(ZipArchive $zipArchive) {
        $raw = $zipArchive->getFromName(self::MANIFEST);
        $data = preg_replace('/\r\n/', "\n", $raw);
        $manifest = $this->parseManifest($data);
        print_r($manifest);
        return $manifest;
    }
    
    public function findPluginEntries($manifest) {
        $entries = [];
        foreach($manifest as $section) {
            if(isset($section[self::PLUGIN_KEY]) && $section[self::PLUGIN_KEY] === 'true') {
                // the comparison above must be string, not boolean.
                $entries[] = $section['name'];
            }
        }
        return $entries;
    }
    
    public function getPluginXml(ZipArchive $zipArchive, $entry) {
        $raw = $zipArchive->getFromName($entry);
        $xml = simplexml_load_string($raw);
        if( ! $xml) {
            throw new Exception("Cannot read plugin xml description. " . $zipArchive->getStatusString());
        }
        return $xml;
    }
    
    public function parseManifest($raw) {
        $manifest = preg_replace('/\r\n/', "\n", $raw);
        $sections = [];
        
        $blocks = preg_split('/\n\s*\n/s', $manifest);
        foreach($blocks as $block) {
            if(ctype_space($block)) {
                continue;
            }
            $block = preg_replace("/\n\s(\S)/", '\1', $block);
            $keys = [];
            $lines = preg_split('/\n/', $block);
            foreach($lines as $line) {
                if(!$line) {
                    continue;
                }
                list($k, $v) = preg_split('/\s*:\s*/', $line);
                $keys[mb_convert_case($k, MB_CASE_LOWER)] = $v;
            }
            if(count($keys) > 0) {
                $sections[] = $keys;
            }
        }
        return $sections;
    }
    
    /**
     * Find a property string in a LOCKSS plugin.xml file.
     *
     * @param SimpleXMLElement $xml
     * @param string $propName
     *
     * @return string
     *
     * @throws Exception
     */
    public function findXmlPropString(SimpleXMLElement $xml, $propName) {
        $data = $xml->xpath("//entry[string[1]/text() = '{$propName}']/string[2]");
        if (count($data) === 1) {
            return (string)$data[0];
        }
        if (count($data) === 0) {
            return;
        }
        throw new Exception('Too many entry elements for property string '.$propName);
    }
    
    /**
     * Find a list element in a LOCKSS plugin.xml file.
     *
     * @param SimpleXMLElement $xml
     * @param type $propName
     *
     * @return SimpleXMLElement
     *
     * @throws Exception
     */
    public function findXmlPropElement(SimpleXMLElement $xml, $propName) {
        $data = $xml->xpath("//entry[string[1]/text() = '{$propName}']/list");
        if (count($data) === 1) {
            return $data[0];
        }
        if (count($data) === 0) {
            return;
        }
        throw new Exception('Too many entry elements for property element'.$propName);
    }
    
    public function importChildren(PluginProperty $property, SimpleXMLElement $value) {
        $childProperty = new PluginProperty();
        $childProperty->setParent($property);
        $property->addChild($childProperty);
        $childProperty->setPlugin($property->getPlugin());
        $childProperty->setPropertyKey($value->getName());
        $childProperty->setIsList(true);
        $this->em->persist($childProperty);
        
        foreach($value->children() as $key => $value) {
            $leaf = new PluginProperty();
            $leaf->setParent($childProperty);
            $childProperty->addChild($leaf);
            $leaf->setPlugin($property->getPlugin());
            $leaf->setPropertyKey($key);
            $leaf->setPropertyValue((string)$value);
            $this->em->persist($leaf);
        }
        return $childProperty;                
    }
    
    public function newPluginConfig(Plugin $plugin, $name, SimpleXMLElement $value) {
        $property = new PluginProperty();
        $property->setPlugin($plugin);
        $property->setPropertyKey($name);
        $property->setIsList(true);
        $this->em->persist($property);
        foreach($value->children() as $child) {
            $childProp = $this->importChildren($property, $child);
        }
        return $property;
    }
    
    /**
     * Generate and persist a new Plugins object.
     *
     * @param Plugin                  $plugin
     * @param string                  $name
     * @param SimpleXMLElement        $value
     *
     * @return PluginProperty
     */
    public function newPluginProperty(Plugin $plugin, $name, SimpleXMLElement $value = null) {
        $property = new PluginProperty();
        $property->setPlugin($plugin);
        $property->setPropertyKey($name);
        if ($value !== null) {
            switch ($value->getName()) {
                // this is the name of the XML element defining the property.
                case 'string':
                    $property->setPropertyValue((string) $value);
                    break;
                case 'list':
                    $values = array();
                    foreach ($value->children() as $child) {
                        $values[] = (string) $child;
                    }
                    $property->setPropertyValue($values);
                    $property->setIsList(true);
                    break;
                default:                    
                    throw new Exception("Cannot import simple property {$name}.");
            }
        }
        $this->em->persist($property);

        return $property;
    }
}
