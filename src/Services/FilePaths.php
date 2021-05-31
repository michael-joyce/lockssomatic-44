<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Au;
use App\Entity\ContentProvider;
use App\Entity\Deposit;
use App\Entity\Pln;
use App\Entity\Plugin;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Construct file paths for different elements of the application.
 */
class FilePaths {
    /**
     * PSR/Log compatible logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The Kernel environment root.
     *
     * @var string
     */
    private $root;

    /**
     * Download directory from the parameters.yml file.
     *
     * @var string
     */
    private $downloadDir;

    /**
     * Filesystem wrapper.
     *
     * @var Filesystem
     */
    private $fs;

    /**
     * Build the service.
     *
     * @param string $root
     * @param string $downloadDir
     */
    public function __construct(LoggerInterface $logger, $root, $downloadDir) {
        $this->fs = new Filesystem();
        $this->logger = $logger;
        $this->root = $root;
        $this->downloadDir = $downloadDir;
    }

    public function setRoot($root) : void {
        $this->root = $root;
    }

    /**
     * Get the root file system path.
     *
     * @return string
     */
    public function getRootPath() {
        return $this->root;
    }

    /**
     * Get the file path for the cache directory.
     *
     * @return string
     */
    public function getCacheDownloadDir() {
        return implode('/', [
            $this->getRootPath(),
            $this->downloadDir,
        ]);
    }

    /**
     * Get the file path for a downloadable content item.
     *
     * LOCKSS boxes do not download content from LOM. This is for the staging
     * server to fetch content from the PLN.
     *
     * @return string
     */
    public function getDownloadContentPath(Deposit $deposit) {
        return implode('/', [
            $this->getCacheDownloadDir(),
            $deposit->getUuid(),
            $deposit->getId(),
            basename($deposit->getFilename()),
        ]);
    }

    /**
     * Get the root directory for lockss files.
     *
     * @return string
     */
    public function getLockssDir() {
        return implode('/', [
            $this->getRootPath(),
            'data',
            'lockss',
        ]);
    }

    /**
     * Get the directory for uploaded plugin files.
     *
     * @return string
     */
    public function getPluginsDir() {
        return implode('/', [
            $this->getLockssDir(),
            'plugins',
        ]);
    }

    /**
     * Get a LOCKSS keystore directory.
     *
     * This is where LOM stores the java keystores. They are served from a
     * different location.
     *
     * @return string
     */
    public function getLockssKeystoreDir(Pln $pln) {
        return implode('/', [
            $this->getLockssDir(),
            'keystores',
            $pln->getId(),
        ]);
    }

    /**
     * Get the path to exported lockss configuration files.
     *
     * @return string
     */
    public function getConfigsDir(Pln $pln) {
        return implode('/', [
            $this->getRootPath(),
            'data',
            'plnconfigs',
            $pln->getId(),
        ]);
    }

    /**
     * Get the complete path to the export lockss.xml file for one PLN.
     *
     * @return string
     */
    public function getLockssXmlFile(Pln $pln) {
        return implode('/', [
            $this->getConfigsDir($pln),
            'properties',
            'lockss.xml',
        ]);
    }

    /**
     * Get the directory for exported plugins for a PLN.
     *
     * @return string
     */
    public function getPluginsExportDir(Pln $pln) {
        return implode('/', [
            $this->getConfigsDir($pln),
            'plugins',
        ]);
    }

    /**
     * Get the path for one exported plugin in a PLN.
     *
     * @return string
     */
    public function getPluginsExportFile(Pln $pln, Plugin $plugin) {
        return implode('/', [
            $this->getPluginsExportDir($pln),
            $plugin->getOriginalFilename(),
        ]);
    }

    /**
     * Get the path to the manifest file for the plugins in a PLN.
     *
     * @return string
     */
    public function getPluginsManifestFile(Pln $pln) {
        return implode('/', [
            $this->getPluginsExportDir($pln),
            'index.html',
        ]);
    }

    /**
     * Get the path to the manifests for a PLN.
     *
     * @return string
     */
    public function getManifestDir(Pln $pln, ContentProvider $provider) {
        return implode('/', [
            $this->getConfigsDir($pln),
            'manifests',
            $provider->getContentOwner()->getId(),
            $provider->getId(),
        ]);
    }

    /**
     * Get the path to a manifest for an AU.
     *
     * @return string
     */
    public function getManifestPath(Au $au) {
        return implode('/', [
            $this->getManifestDir($au->getPln(), $au->getContentprovider()),
            'manifest_' . $au->getId() . '.html',
        ]);
    }

    /**
     * Get the path to the titles database directory.
     *
     * @return string
     */
    public function getTitleDbDir(Pln $pln, ContentProvider $provider) {
        return implode('/', [
            $this->getConfigsDir($pln),
            'titledbs',
            $provider->getContentOwner()->getId(),
            $provider->getId(),
        ]);
    }

    public function getTitleDbPath(ContentProvider $provider, $id) {
        return implode('/', [
            $this->getTitleDbDir($provider->getPln(), $provider),
            'titledb_' . $id . '.xml',
        ]);
    }
}
