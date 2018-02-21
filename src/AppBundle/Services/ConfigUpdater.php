<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Pln;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Update the configuration properties of a PLN. 
 */
class ConfigUpdater {
    
    /**
     * @var UrlGeneratorInterface
     */
    private $generator;
    
    public function __construct(UrlGeneratorInterface $generator) {
        $this->generator = $generator;
    }
    
    /**
     * Update the list of peers in the PLN configuration properties.
     * 
     * @param Pln $pln
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
     */
    public function updateTitleDbs(Pln $pln) {
        $urls = [];
        foreach($pln->getContentProviders() as $provider) {
            $owner = $provider->getContentOwner();
            $url = $this->generator->generate('lockss_titledb', array(
                'plnId' => $pln->getId(),
                'ownerId' => $owner->getId(),
                'providerId' => $provider->getId(),
                'filename' => "titledb_{$provider->getId()}.xml",
            ), UrlGeneratorInterface::ABSOLUTE_URL);
            $urls[] = $url;
        }
        $pln->setProperty('org.lockss.titleDbs', $urls);
    }
        
    /**
     * Set the location of the PLN keystore in the config properties.
     * 
     * @param Pln $pln
     */
    public function updateKeystoreLocation(Pln $pln) {
        $filename = $pln->getKeystoreFilename();
        if( ! $filename) {
            $pln->removeProperty('org.lockss.plugin.keystore.location');
            return;
        }
        $url = $this->generator->generate('lockss_keystore', array(
            'plnId' => $pln->getId(),
        ), UrlGeneratorInterface::ABSOLUTE_URL);
        $pln->setProperty('org.lockss.plugin.keystore.location', $url);        
    }
    
    /**
     * Update the PLN config properties to match the credentials stored in LOM.
     * 
     * @param Pln $pln
     */
    public function updateAuthentication(Pln $pln) {
        $username = $pln->getUsername();
        if( ! $username) {
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
     */
    public function updateContentUi(Pln $pln) {
        $prefix = 'org.lockss.contentui';
        $pln->setProperty("{$prefix}.start", $pln->getEnableContentUi() ? "true" : "false");
        $pln->setProperty("{$prefix}.port", $pln->getContentPort());
    }
    
    public function updatePluginRegistries(Pln $pln) {
        $url = $this->generator->generate('lockss_plugin_list', array(
            'plnId' => $pln->getId(),
        ), UrlGeneratorInterface::ABSOLUTE_URL);
        $pln->setProperty('org.lockss.plugin.registries', $url);
    }
    
    /**
     * Update all the PLN config properties.
     * 
     * @param Pln $pln
     */
    public function update(Pln $pln) {
        $pln->clearProperties();
        $this->updatePeerList($pln);
        $this->updateTitleDbs($pln);
        $this->updateKeystoreLocation($pln);
        $this->updateAuthentication($pln);
        $this->updateContentUi($pln);
        $this->updatePluginRegistries($pln);
    }
    
}
