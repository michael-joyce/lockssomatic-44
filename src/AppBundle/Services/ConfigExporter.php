<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Content;
use AppBundle\Entity\Pln;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Templating\EngineInterface;

/**
 * Description of ConfigExporter
 */
class ConfigExporter {

    /**
     * @var EntityManagerInterface
     */
    private $em;
    
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

    public function __construct(EntityManagerInterface $em, EngineInterface $templating, FilePaths $fp) {
        $this->em = $em;
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
        $keystore = $pln->getKeystorePath();
        if( ! $keystore) {
            return;
        }
        $path = $this->fp->getPluginsExportDir($pln);
        $this->fs->copy($keystore, "{$path}/lockss.keystore");
    }
    
    /**
     * Export the java plugins.
     * 
     * @param Pln $pln
     */
    public function exportPlugins(Pln $pln) {
        foreach($pln->getPlugins() as $plugin) {
            if( !file_exists($plugin->getPath())) {
                continue;
            }
            $path = $this->fp->getPluginsExportFile($pln, $plugin);
            $this->fs->copy($plugin->getPath(), $path);
        }
        $html = $this->templating->render('AppBundle:lockss:plugin_list.html.twig', array(
            'pln' => $pln,
        ));
        $this->fs->dumpFile($this->fp->getPluginsManifestFile($pln), $html);
    }
    
    /**
     * Export the manifests for a PLN.
     * 
     * @param Pln $pln
     */
    public function exportManifests(Pln $pln) {
        $repo = $this->em->getRepository(Content::class);
        foreach($pln->getAus() as $au) {
            $manifestPath = $this->fp->getManifestPath($au);
            $iterator = $repo->auQuery($au)->iterate();
            $html = $this->templating->render('AppBundle:lockss:manifest.html.twig', array(
                'pln' => $pln,
                'content' => $iterator,
            ));
            $this->fs->dumpFile($manifestPath, $html);
        }
    }
    
    /**
     * Export the lOCKSS titledbs for a PLN.
     * 
     * @param Pln $pln
     */
    public function exportTitleDbs(Pln $pln) {
        foreach($pln->getContentProviders() as $provider) {
            $titleDbPath = $this->fp->getTitleDbPath($provider);
            $xml = $this->templating->render('AppBundle:lockss:titledb.xml.twig', array(
                'aus' => $provider->getAus(),
            ));
            $this->fs->dumpFile($titleDbPath, $xml);
        }
    }
    
    public function export(Pln $pln) {
        $this->exportLockssXml($pln);
        $this->exportKeystore($pln);
        $this->exportPlugins($pln);
        $this->exportManifests($pln);
        $this->exportTitleDbs($pln);
    }
}
