<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Pln;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Templating\EngineInterface;

/**
 * Export all the configuration for a PLN.
 */
class ConfigExporter {

    /**
     * Dependency-injected number of AUs in a title db file.
     *
     * @var int
     */
    private $ausPerTitleDb;

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

    /**
     * @var AuManager
     */
    private $manager;

    /**
     *
     */
    public function __construct($ausPerTitleDb, EntityManagerInterface $em, EngineInterface $templating, FilePaths $fp, AuManager $manager) {
        $this->ausPerTitleDb = $ausPerTitleDb;
        $this->em = $em;
        $this->templating = $templating;
        $this->fp = $fp;
        $this->fs = new Filesystem();
        $this->manager = $manager;
    }

    /**
     *
     */
    public function setFilePaths(FilePaths $fp) {
        $this->fp = $fp;
    }

    /**
     *
     */
    public function setEntityManager(EntityManagerInterface $em) {
        $this->em = $em;
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
        if (!$keystore) {
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
        foreach ($pln->getPlugins() as $plugin) {
            if (!file_exists($plugin->getPath())) {
                throw new Exception("Cannot find {$plugin->getPath()} to export plugin.");
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
        $repo = $this->em->getRepository(Deposit::class);
        foreach ($pln->getAus() as $au) {
            $manifestPath = $this->fp->getManifestPath($au);
            $iterator = $this->manager->auDeposits($au);
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
        foreach ($pln->getContentProviders() as $provider) {
            $aus = $provider->getAus();
            for ($i = 0; $i < ceil($aus->count() / $this->ausPerTitleDb); $i++) {
                $slice = $aus->slice($i * $this->ausPerTitleDb, $this->ausPerTitleDb);
                $titleDbPath = $this->fp->getTitleDbPath($provider, $i + 1);
                $xml = $this->templating->render('AppBundle:lockss:titledb.xml.twig', array(
                'aus' => $slice,
                ));
                $this->fs->dumpFile($titleDbPath, $xml);
            }
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function export(Pln $pln) {
        $this->exportLockssXml($pln);
        $this->exportKeystore($pln);
        $this->exportPlugins($pln);
        $this->exportManifests($pln);
        $this->exportTitleDbs($pln);
    }

}
