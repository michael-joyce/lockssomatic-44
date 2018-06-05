<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Pln;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Update the configuration properties of a PLN.
 */
class ConfigUpdater {

    /**
     * @var string
     */
    private $ausPerTitleDb;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var AuManager
     */
    private $auManager;

    /**
     *
     * @param int $ausPerTitleDb
     *   Maximum number of AUs to include in a titledb.xml file.
     * @param UrlGeneratorInterface $urlGenerator
     *   The Symfony URL generator.
     * @param AuPropertyGenerator $auManager
     *   The AU property generator service.
     */
    public function __construct($ausPerTitleDb, UrlGeneratorInterface $urlGenerator, AuManager $auManager) {
        $this->ausPerTitleDb = $ausPerTitleDb;
        $this->urlGenerator = $urlGenerator;
        $this->auManager = $auManager;
    }

    /**
     * Update the list of peers in the PLN configuration properties.
     *
     * @param Pln $pln
     *   The pln to update.
     */
    public function updatePeerList(Pln $pln) {
        $boxes = $pln->getBoxes();
        $list = array();
        foreach ($boxes as $box) {
            $list[] = "{$box->getProtocol()}:[{$box->getIpAddress()}]:{$box->getPort()}";
        }
        $pln->setProperty('org.lockss.id.initialV3PeerList', $list);
    }

    /**
     * Set the list of title db URLs in the PLN config properties.
     *
     * @param Pln $pln
     *   The pln to update.
     */
    public function updateTitleDbs(Pln $pln) {
        $urls = [];
        foreach ($pln->getContentProviders() as $provider) {
            $owner = $provider->getContentOwner();
            $aus = $provider->getAus();
            for ($i = 1; $i <= ceil($aus->count() / $this->ausPerTitleDb); $i++) {
                $url = $this->urlGenerator->generate('lockss_titledb', array(
                    'plnId' => $pln->getId(),
                    'ownerId' => $owner->getId(),
                    'providerId' => $provider->getId(),
                    'id' => $i,
                ), UrlGeneratorInterface::ABSOLUTE_URL);
                $urls[] = $url;
            }
        }
        $pln->setProperty('org.lockss.titleDbs', $urls);
    }

    /**
     * Update the AU configurations by generating the properties for them.
     *
     * @param Pln $pln
     *   The pln to update.
     */
    public function updateAuConfigs(Pln $pln) {
        foreach ($pln->getAus() as $au) {
            if ($au->hasAuProperties()) {
                continue;
            }
            $this->auManager->generateProperties($au);
        }
    }

    /**
     * Set the location of the PLN keystore in the config properties.
     *
     * @param Pln $pln
     *   The pln to update.
     */
    public function updateKeystoreLocation(Pln $pln) {
        $filename = $pln->getKeystoreFilename();
        if (!$filename) {
            $pln->removeProperty('org.lockss.plugin.keystore.location');
            return;
        }
        $url = $this->urlGenerator->generate('lockss_keystore', array(
        'plnId' => $pln->getId(),
        ), UrlGeneratorInterface::ABSOLUTE_URL);
        $pln->setProperty('org.lockss.plugin.keystore.location', $url);
    }

    /**
     * Update the PLN config properties to match the credentials stored in LOM.
     *
     * @param Pln $pln
     *   The pln to update.
     */
    public function updateAuthentication(Pln $pln) {
        $username = $pln->getUsername();
        if (!$username) {
            return;
        }
        $prefix = 'org.lockss.ui.users.lomauth';
        $hash = hash('SHA256', $pln->getPassword());
        $pln->setProperty("{$prefix}.user", $username);
        $pln->setProperty("{$prefix}.password", "SHA-256:$hash");
        $pln->setProperty("{$prefix}.roles", 'accessContentRole');
    }

    /**
     * Update the PLN config properties to match the content UI settings in LOM.
     *
     * @param Pln $pln
     *   The pln to update.
     */
    public function updateContentUi(Pln $pln) {
        $prefix = 'org.lockss.contentui';
        $pln->setProperty("{$prefix}.start", $pln->getEnableContentUi() ? "true" : "false");
        $pln->setProperty("{$prefix}.port", $pln->getContentPort());
        $pln->setProperty("org.lockss.ui.contactEmail", $pln->getEmail());
    }

    /**
     * Update the plugin registries for a PLN.
     *
     * @param Pln $pln
     *   The pln to update.
     */
    public function updatePluginRegistries(Pln $pln) {
        $url = $this->urlGenerator->generate('lockss_plugin_list', array(
            'plnId' => $pln->getId(),
        ), UrlGeneratorInterface::ABSOLUTE_URL);
        $pln->setProperty('org.lockss.plugin.registries', $url);
    }

    /**
     * Update all the PLN config properties.
     *
     * @param Pln $pln
     *
     * @codeCoverageIgnore
     */
    public function update(Pln $pln) {
        $this->updatePeerList($pln);
        $this->updateTitleDbs($pln);
        $this->updateAuConfigs($pln);
        $this->updateKeystoreLocation($pln);
        $this->updateAuthentication($pln);
        $this->updateContentUi($pln);
        $this->updatePluginRegistries($pln);
    }

}
