<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Pln;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Templating\EngineInterface;

/**
 * Description of ConfigExporter
 */
class ConfigExporter {

    /**
     * @var EngineInterface
     */
    private $templating;
    
    /**
     * @var FilePaths
     */
    private $fp;
    
    /**
     * @var Filesystem
     */
    private $fs;

    public function __construct(EngineInterface $templating, FilePaths $fp) {
        $this->templating = $templating;
        $this->fp = $fp;
        $this->fs = new Filesystem();
    }
    
    /**
     * Export the lockss.xml configuration file.
     * 
     * @param Pln $pln
     */
    public function exportLockssXml(Pln $pln) {
        $xml = $this->templating->render('AppBundle:lockss:lockss.xml.twig', array(
            'pln' => $pln,
        ));
        $path = $this->fp->getLockssXmlFile($pln);
        $this->fs->dumpFile($path, $xml);
    }
    
    /**
     * Export a PLN's java keystore.
     * 
     * @param Pln $pln
     */
    public function exportKeystore(Pln $pln) {
        
    }
    
    /**
     * Export the java plugins.
     * 
     * @param Pln $pln
     */
    public function exportPlugins(Pln $pln) {
        
    }
    
    /**
     * Export the manifests for a PLN.
     * 
     * @param Pln $pln
     */
    public function exportManifests(Pln $pln) {
        
    }
    
    /**
     * Export the lOCKSS titledbs for a PLN.
     * 
     * @param Pln $pln
     */
    public function exportAus(Pln $pln) {
        
    }
    
    public function export(Pln $pln) {
        $this->exportLockssXml($pln);
        $this->exportKeystore($pln);
        $this->exportPlugins($pln);
        $this->exportManifests($pln);
        $this->exportAus($pln);
    }
}
