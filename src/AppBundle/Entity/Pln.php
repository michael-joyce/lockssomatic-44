<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;
use SplFileInfo;

/**
 * Pln.
 *
 * @ORM\Table(name="pln")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PlnRepository")
 */
class Pln extends AbstractEntity {

    const KEYSTORE_MIMETYPES = array(
        'application/x-java-keystore',
    );

    /**
     * LOCKSS will only recognize these properties in an XML file if they
     * are lists.
     */
    const LIST_REQUIRED = array(
        'org.lockss.id.initialV3PeerList',
        'org.lockss.titleDbs',
        'org.lockss.plugin.registries',
    );

    /**
     * Name of the PLN.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * Description of the PLN.
     *
     * @var string
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * The username for LOCKSSOMatic to communicate with the box. Not in the
     * lockss.xml file.
     *
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=64, nullable=true)
     */
    private $username;

    /**
     * The password for LOCKSSOMatic to communicate with the box.
     *
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=64, nullable=true)
     */
    private $password;

    /**
     * If true, the lockss ContentUI subsystem will be enabled.
     *
     * @var bool
     * @ORM\Column(name="enable_content_ui", type="boolean", nullable=false)
     */
    private $enableContentUi;

    /**
     * Unix port for the the content server to listen on, if enabled.
     *
     * @var int
     * @ORM\Column(name="content_port", type="integer", nullable=false);
     */
    private $contentPort;

    /**
     * Java Keystore file.
     *
     * @ORM\Column(name="keystore_path", type="string", length=255, nullable=true)
     */
    private $keystore;

    /**
     * PLN Properties, as defined by the lockss.xml file and LOCKSSOMatic.
     *
     * @ORM\Column(name="property", type="array", nullable=false);
     *
     * @var array
     */
    private $properties;

    /**
     * A list of all AUs in the PLN. Probably very large.
     *
     * @ORM\OneToMany(targetEntity="Au", mappedBy="pln")
     *
     * @var Collection|Au[]
     */
    private $aus;

    /**
     * List of boxes in the PLN.
     *
     * @ORM\OneToMany(targetEntity="Box", mappedBy="pln");
     *
     * @var Collection|Box[]
     */
    private $boxes;

    /**
     * List of content providers for this PLN. Each provider is associated with
     * exactly one PLN.
     *
     * @ORM\OneToMany(targetEntity="ContentProvider", mappedBy="pln")
     *
     * @var Pln[]|Collection
     */
    private $contentProviders;

    /**
     *
     */
    public function __construct() {
        parent::__construct();
        $this->enableContentUi = false;
        $this->contentPort = '8080';
        $this->properties = array();
        $this->contentProviders = new ArrayCollection();
        $this->boxes = new ArrayCollection();
        $this->aus = new ArrayCollection();
    }

    /**
     *
     */
    public function __toString() {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Pln
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Pln
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set username.
     *
     * @param string $username
     *
     * @return Pln
     */
    public function setUsername($username) {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username.
     *
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return Pln
     */
    public function setPassword($password) {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Set enableContentUi.
     *
     * @param bool $enableContentUi
     *
     * @return Pln
     */
    public function setEnableContentUi($enableContentUi) {
        $this->enableContentUi = $enableContentUi;

        return $this;
    }

    /**
     * Get enableContentUi.
     *
     * @return bool
     */
    public function getEnableContentUi() {
        return $this->enableContentUi;
    }

    /**
     * Set contentPort.
     *
     * @param int $contentPort
     *
     * @return Pln
     */
    public function setContentPort($contentPort) {
        $this->contentPort = $contentPort;

        return $this;
    }

    /**
     * Get contentPort.
     *
     * @return int
     */
    public function getContentPort() {
        return $this->contentPort;
    }

    public function clearProperties() {
        $this->properties = [];
        return $this;
    }

    /**
     * Get properties.
     *
     * @return array
     */
    public function getProperties() {
        return $this->properties;
    }

    /**
     * @param string $key
     * @param string|array $value
     * @return Pln
     */
    public function setProperty($key, $value) {
        if(in_array($key, self::LIST_REQUIRED)) {
            if(is_array($value)) {
                $this->properties[$key] = $value;
            } else {
                $this->properties[$key] = [$value];
            }
        } else {
            if(is_array($value) && count($value) === 1) {
                $this->properties[$key] = $value[0];
            } else {
                $this->properties[$key] = $value;
            }
        }
        return $this;
    }

    public function getProperty($key) {
        if( !array_key_exists($key, $this->properties)) {
            return null;
        }
        return $this->properties[$key];
    }

    public function removeProperty($key) {
        unset($this->properties[$key]);
    }

    /**
     * Add aus.
     *
     * @param Au $au
     *
     * @return Pln
     */
    public function addAu(Au $au) {
        if (!$this->aus->contains($au)) {
            $this->aus->add($au);
        }

        return $this;
    }

    /**
     * Remove aus.
     *
     * @param Au $au
     */
    public function removeAu(Au $au) {
        $this->aus->removeElement($au);
    }

    /**
     * Get aus.
     *
     * @return Collection
     */
    public function getAus() {
        return $this->aus;
    }

    /**
     * Add box.
     *
     * @param Box $box
     *
     * @return Pln
     */
    public function addBox(Box $box) {
        $this->boxes[] = $box;

        return $this;
    }

    /**
     * Remove box.
     *
     * @param Box $box
     */
    public function removeBox(Box $box) {
        $this->boxes->removeElement($box);
    }

    /**
     * Get boxes.
     *
     * @return Collection
     */
    public function getBoxes() {
        return $this->boxes;
    }

    /**
     * Set keystore.
     *
     * @param string $keystore
     *   Path to the keystore file.
     *
     * @return Pln
     */
    public function setKeystore($keystore) {
        $this->keystore = $keystore;

        return $this;
    }

    /**
     * Get keystore.
     *
     * @return Keystore
     */
    public function getKeystorePath() {
        return $this->keystore;
    }

    /**
     *
     */
    public function getKeystoreFilename() {
        $fileinfo = new SplFileInfo($this->keystore);
        return $fileinfo->getBasename();
    }

    /**
     * Add contentProvider.
     *
     * @param ContentProvider $contentProvider
     *
     * @return Pln
     */
    public function addContentProvider(ContentProvider $contentProvider) {
        $this->contentProviders[] = $contentProvider;

        return $this;
    }

    /**
     * Remove contentProvider.
     *
     * @param ContentProvider $contentProvider
     */
    public function removeContentProvider(ContentProvider $contentProvider) {
        $this->contentProviders->removeElement($contentProvider);
    }

    /**
     * Get contentProviders.
     *
     * @return Collection|ContentProvider[]
     */
    public function getContentProviders() {
        return $this->contentProviders;
    }

    /**
     * @return Plugin[]
     */
    public function getPlugins() {
        $plugins = [];
        foreach($this->contentProviders as $provider) {
            if( ! in_array($provider->getPlugin(), $plugins)) {
                $plugins[] = $provider->getPlugin();
            }
        }
        return $plugins;
    }

}
