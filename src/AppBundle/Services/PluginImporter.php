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

    private $em;
    
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }
    
    public function import(Plugin $plugin) {
        $zipArchive = new ZipArchive();
        $res = $zipArchive->open($plugin->getJarFile()->getPathname());
        if($res !== true) {
            throw new Exception("Cannot read plugin jar file. Error code {$res}.");
        }
        $raw = $zipArchive->getFromName('META-INF/MANIFEST.MF');
        $manifest = preg_replace('/\r\n/', "\n", $raw);
        $property = new PluginProperty();
        $property->setPlugin($plugin);
        $plugin->addPluginProperty($property);
        $property->setPropertyKey("MANIFEST");
        $property->setPropertyValue($this->parseManifest($manifest));
        $this->em->persist($property);
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
                $keys[$k] = $v;
            }
            $sections[] = $keys;
        }
        return $sections;
    }
}
