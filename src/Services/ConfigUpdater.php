<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Pln;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Update the configuration properties of a PLN.
 */
class ConfigUpdater
{
    /**
     * Number of AUs in a titledb XML file.
     *
     * @var int
     */
    private $ausPerTitleDb;

    /**
     * URL Generator.
     *
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * AU Manager service.
     *
     * @var AuManager
     */
    private $auManager;

    /**
     * Construct the updater service.
     *
     * @param int $ausPerTitleDb
     */
    public function __construct($ausPerTitleDb, UrlGeneratorInterface $urlGenerator, AuManager $auManager) {
        $this->ausPerTitleDb = $ausPerTitleDb;
        $this->urlGenerator = $urlGenerator;
        $this->auManager = $auManager;
    }

    /**
     * Update the list of peers in the PLN configuration properties.
     */
    public function updatePeerList(Pln $pln) : void {
        $boxes = $pln->getBoxes();
        $list = [];

        foreach ($boxes as $box) {
            $list[] = "{$box->getProtocol()}:[{$box->getIpAddress()}]:{$box->getPort()}";
        }
        $pln->setProperty('org.lockss.id.initialV3PeerList', $list);
    }

    /**
     * Set the list of title db URLs in the PLN config properties.
     */
    public function updateTitleDbs(Pln $pln) : void {
        $urls = [];

        foreach ($pln->getContentProviders() as $provider) {
            $owner = $provider->getContentOwner();
            $aus = $provider->getAus();

            for ($i = 1; $i <= ceil($aus->count() / $this->ausPerTitleDb); $i++) {
                $url = $this->urlGenerator->generate('lockss_titledb', [
                    'plnId' => $pln->getId(),
                    'ownerId' => $owner->getId(),
                    'providerId' => $provider->getId(),
                    'id' => $i,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
                $urls[] = $url;
            }
        }
        $pln->setProperty('org.lockss.titleDbs', $urls);
    }

    /**
     * Update the AU configurations by generating the properties for them.
     */
    public function updateAuConfigs(Pln $pln) : void {
        foreach ($pln->getAus() as $au) {
            if ($au->hasAuProperties()) {
                continue;
            }
            $this->auManager->generateProperties($au);
        }
    }

    /**
     * Set the location of the PLN keystore in the config properties.
     */
    public function updateKeystoreLocation(Pln $pln) : void {
        $filename = $pln->getKeystoreFilename();
        if ( ! $filename) {
            $pln->removeProperty('org.lockss.plugin.keystore.location');

            return;
        }
        $url = $this->urlGenerator->generate('lockss_keystore', [
            'plnId' => $pln->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $pln->setProperty('org.lockss.plugin.keystore.location', $url);
    }

    /**
     * Update the PLN config properties to match the credentials stored in LOM.
     */
    public function updateAuthentication(Pln $pln) : void {
        $username = $pln->getUsername();
        if ( ! $username) {
            return;
        }
        $prefix = 'org.lockss.ui.users.lomauth';
        $hash = hash('SHA256', $pln->getPassword());
        $pln->setProperty("{$prefix}.user", $username);
        $pln->setProperty("{$prefix}.password", "SHA-256:{$hash}");
        $pln->setProperty("{$prefix}.roles", 'accessContentRole');
    }

    /**
     * Update the PLN config properties to match the content UI settings in LOM.
     */
    public function updateContentUi(Pln $pln) : void {
        $prefix = 'org.lockss.contentui';
        $pln->setProperty("{$prefix}.start", $pln->getEnableContentUi() ? 'true' : 'false');
        $pln->setProperty("{$prefix}.port", $pln->getContentPort());
        $pln->setProperty('org.lockss.ui.contactEmail', $pln->getEmail());
    }

    /**
     * Update the plugin registries for a PLN.
     */
    public function updatePluginRegistries(Pln $pln) : void {
        $url = $this->urlGenerator->generate('lockss_plugin_list', [
            'plnId' => $pln->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $pln->setProperty('org.lockss.plugin.registries', $url);
    }

    /**
     * Update all the PLN config properties.
     *
     * @codeCoverageIgnore
     */
    public function update(Pln $pln) : void {
        $this->updatePeerList($pln);
        $this->updateTitleDbs($pln);
        $this->updateAuConfigs($pln);
        $this->updateKeystoreLocation($pln);
        $this->updateAuthentication($pln);
        $this->updateContentUi($pln);
        $this->updatePluginRegistries($pln);
    }
}
