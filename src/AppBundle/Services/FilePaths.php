<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Pln;
use AppBundle\Entity\Plugin;
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
     * @param LoggerInterface $logger
     * @param string $root
     * @param string $downloadDir
     */
    public function __construct(LoggerInterface $logger, $root, $downloadDir) {
        $this->fs = new Filesystem();
        $this->logger = $logger;
        $this->root = $root;
        $this->downloadDir = $downloadDir;
    }

    public function setRoot($root) {
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
        $path = implode('/', array(
            $this->getRootPath(),
            $this->downloadDir,
        ));
        return $path;
    }

    /**
     * Get the file path for a downloadable content item.
     *
     * LOCKSS boxes do not download content from LOM. This is for the staging
     * server to fetch content from the PLN.
     *
     * @param Deposit $deposit
     *
     * @return string
     */
    public function getDownloadContentPath(Deposit $deposit) {
        $path = implode('/', array(
            $this->getCacheDownloadDir(),
            $deposit->getUuid(),
            $deposit->getId(),
            basename($deposit->getFilename()),
        ));
        return $path;
    }

    /**
     * Get the root directory for lockss files.
     *
     * @return string
     */
    public function getLockssDir() {
        $path = implode('/', array(
            $this->getRootPath(),
            'data',
            'lockss',
        ));

        return $path;
    }

    /**
     * Get the directory for uploaded plugin files.
     *
     * @return string
     */
    public function getPluginsDir() {
        $path = implode('/', array(
            $this->getLockssDir(),
            'plugins',
        ));

        return $path;
    }

    /**
     * Get a LOCKSS keystore directory.
     *
     * This is where LOM stores the java keystores. They are served from a
     * different location.
     *
     * @param Pln $pln
     *
     * @return string
     */
    public function getLockssKeystoreDir(Pln $pln) {
        $path = implode('/', array(
            $this->getLockssDir(),
            'keystores',
            $pln->getId(),
        ));

        return $path;
    }

    /**
     * Get the path to exported lockss configuration files.
     *
     * @param Pln $pln
     *
     * @return string
     */
    public function getConfigsDir(Pln $pln) {
        $path = implode('/', array(
            $this->getRootPath(),
            'data',
            'plnconfigs',
            $pln->getId(),
        ));

        return $path;
    }

    /**
     * Get the complete path to the export lockss.xml file for one PLN.
     *
     * @param Pln $pln
     *
     * @return string
     */
    public function getLockssXmlFile(Pln $pln) {
        $path = implode('/', array(
            $this->getConfigsDir($pln),
            'properties',
            'lockss.xml',
        ));

        return $path;
    }

    /**
     * Get the directory for exported plugins for a PLN.
     *
     * @param Pln $pln
     *
     * @return string
     */
    public function getPluginsExportDir(Pln $pln) {
        $path = implode('/', array(
            $this->getConfigsDir($pln),
            'plugins',
        ));

        return $path;
    }

    /**
     * Get the path for one exported plugin in a PLN.
     *
     * @param Pln $pln
     * @param Plugin $plugin
     *
     * @return string
     */
    public function getPluginsExportFile(Pln $pln, Plugin $plugin) {
        $path = implode('/', array(
            $this->getPluginsExportDir($pln),
            $plugin->getOriginalFilename(),
        ));

        return $path;
    }

    /**
     * Get the path to the manifest file for the plugins in a PLN.
     *
     * @param Pln $pln
     *
     * @return string
     */
    public function getPluginsManifestFile(Pln $pln) {
        $path = implode('/', array(
            $this->getPluginsExportDir($pln),
            'index.html',
        ));

        return $path;
    }

    /**
     * Get the path to the manifests for a PLN.
     *
     * @param Pln $pln
     * @param ContentProvider $provider
     *
     * @return string
     */
    public function getManifestDir(Pln $pln, ContentProvider $provider) {
        $path = implode('/', array(
            $this->getConfigsDir($pln),
            'manifests',
            $provider->getContentOwner()->getId(),
            $provider->getId(),
        ));

        return $path;
    }

    /**
     * Get the path to a manifest for an AU.
     *
     * @param Au $au
     *
     * @return string
     */
    public function getManifestPath(Au $au) {
        $path = implode('/', array(
            $this->getManifestDir($au->getPln(), $au->getContentprovider()),
            'manifest_' . $au->getId() . '.html',
        ));

        return $path;
    }

    /**
     * Get the path to the titles database directory.
     *
     * @param Pln $pln
     * @param ContentProvider $provider
     *
     * @return string
     */
    public function getTitleDbDir(Pln $pln, ContentProvider $provider) {
        $path = implode('/', array(
            $this->getConfigsDir($pln),
            'titledbs',
            $provider->getContentOwner()->getId(),
            $provider->getId(),
        ));

        return $path;
    }

    public function getTitleDbPath(ContentProvider $provider, $id) {
        $path = implode('/', array(
            $this->getTitleDbDir($provider->getPln(), $provider),
            'titledb_' . $id . '.xml',
        ));
        return $path;
    }

}
