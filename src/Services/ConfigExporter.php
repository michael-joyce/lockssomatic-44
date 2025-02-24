<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Pln;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

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
     * Doctrine instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Templating engine service.
     *
     * @var Environment
     */
    private $templating;

    /**
     * File path service.
     *
     * @var FilePaths
     */
    private $fp;

    /**
     * File system utility.
     *
     * @var Filesystem
     */
    private $fs;

    /**
     * Au manager service.
     *
     * @var AuManager
     */
    private $manager;

    /**
     * Build the exporter.
     *
     * @param int $ausPerTitleDb
     */
    public function __construct($ausPerTitleDb, EntityManagerInterface $em, Environment $templating, FilePaths $fp, AuManager $manager) {
        $this->ausPerTitleDb = $ausPerTitleDb;
        $this->em = $em;
        $this->templating = $templating;
        $this->fp = $fp;
        $this->fs = new Filesystem();
        $this->manager = $manager;
    }

    /**
     * Set or override the file path service.
     */
    public function setFilePaths(FilePaths $fp) : void {
        $this->fp = $fp;
    }

    /**
     * Set or override the AU manager service.
     */
    public function setAuManager(AuManager $manager) : void {
        $this->manager = $manager;
    }

    public function writeData($data, $path, $asHtml = false) : void {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if ($asHtml) {
            $dom->loadHTML($data, LIBXML_HTML_NOIMPLIED);
        } else {
            $dom->loadXML($data);
        }
        $dom->normalizeDocument();
        if ( ! file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        $dom->save($path);
    }

    /**
     * Export the lockss.xml configuration file.
     */
    public function exportLockssXml(Pln $pln) : void {
        $xml = $this->templating->render('lockss/lockss.xml.twig', [
            'pln' => $pln,
        ]);
        $path = $this->fp->getLockssXmlFile($pln);
        $this->writeData($xml, $path);
    }

    /**
     * Export a PLN's java keystore.
     */
    public function exportKeystore(Pln $pln) : void {
        $keystore = $pln->getKeystorePath();
        if ( ! $keystore) {
            return;
        }
        $path = $this->fp->getPluginsExportDir($pln);
        $this->fs->copy($keystore, "{$path}/lockss.keystore");
    }

    /**
     * Export the java plugins.
     */
    public function exportPlugins(Pln $pln) : void {
        foreach ($pln->getPlugins() as $plugin) {
            if ( ! file_exists($plugin->getPath())) {
                throw new Exception("Cannot find {$plugin->getPath()} to export plugin.");
            }
            $path = $this->fp->getPluginsExportFile($pln, $plugin);
            $this->fs->copy($plugin->getPath(), $path);
        }
        $html = $this->templating->render('lockss/plugin_list.html.twig', [
            'pln' => $pln,
        ]);
        $manifestPath = $this->fp->getPluginsManifestFile($pln);
        $this->writeData($html, $manifestPath, true);
    }

    /**
     * Export the manifests for a PLN.
     */
    public function exportManifests(Pln $pln) : void {
        foreach ($pln->getAus() as $au) {
            $path = $this->fp->getManifestPath($au);
            $iterator = $this->manager->auDeposits($au);
            $html = $this->templating->render('lockss/manifest.html.twig', [
                'pln' => $pln,
                'content' => $iterator,
            ]);
            $this->writeData($html, $path, true);
        }
    }

    /**
     * Export the lOCKSS titledbs for a PLN.
     */
    public function exportTitleDbs(Pln $pln) : void {
        foreach ($pln->getContentProviders() as $provider) {
            $aus = $provider->getAus();

            for ($i = 0; $i < ceil($aus->count() / $this->ausPerTitleDb); $i++) {
                $slice = $aus->slice($i * $this->ausPerTitleDb, $this->ausPerTitleDb);
                $path = $this->fp->getTitleDbPath($provider, $i + 1);
                $xml = $this->templating->render('lockss/titledb.xml.twig', [
                    'aus' => $slice,
                ]);
                $this->writeData($xml, $path);
            }
        }
    }

    /**
     * Export a PLN.
     *
     * @codeCoverageIgnore
     */
    public function export(Pln $pln) : void {
        $this->exportLockssXml($pln);
        $this->exportKeystore($pln);
        $this->exportPlugins($pln);
        $this->exportManifests($pln);
        $this->exportTitleDbs($pln);
    }
}
