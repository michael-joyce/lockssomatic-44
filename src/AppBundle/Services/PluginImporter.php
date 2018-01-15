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
use ZipArchive;

/**
 * Description of PluginImporter
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class PluginImporter {
    
    const MANIFEST = 'META-INF/MANIFEST.MF';

    const PLUGIN_KEY = 'lockss-plugin';
    
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
}
