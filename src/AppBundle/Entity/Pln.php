<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
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
    /**
     * Mime types acceptable for java keystores.
     */
    public const KEYSTORE_MIMETYPES = [
        'application/x-java-keystore',
    ];

    /**
     * LOCKSS will only recognize these properties in an XML file if they are lists.
     */
    public const LIST_REQUIRED = [
        'org.lockss.id.initialV3PeerList',
        'org.lockss.titleDbs',
        'org.lockss.plugin.registries',
    ];

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
     * The username for LOCKSSOMatic to communicate with the box.
     *
     * Not in the lockss.xml file.
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
     * Contact email address for automated notifications.
     *
     * @var string
     * @ORM\Column(name="email", type="string", length=64, nullable=true)
     */
    private $email;

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
     * @var string
     *
     * @ORM\Column(name="keystore_path", type="string", length=255, nullable=true)
     */
    private $keystore;

    /**
     * PLN Properties, as defined by the lockss.xml file and LOCKSSOMatic.
     *
     * @var array
     *
     * @ORM\Column(name="property", type="array", nullable=false);
     */
    private $properties;

    /**
     * A list of all AUs in the PLN. Probably very large.
     *
     * @var Au[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Au", mappedBy="pln")
     */
    private $aus;

    /**
     * List of boxes in the PLN.
     *
     * @var Box[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Box", mappedBy="pln");
     */
    private $boxes;

    /**
     * List of content providers for this PLN.
     *
     * Each provider is associated with exactly one PLN.
     *
     * @var Collection|Pln[]
     *
     * @ORM\OneToMany(targetEntity="ContentProvider", mappedBy="pln")
     */
    private $contentProviders;

    /**
     * Construct the pln objects.
     */
    public function __construct() {
        parent::__construct();
        $this->enableContentUi = false;
        $this->contentPort = '8080';
        $this->properties = [];
        $this->contentProviders = new ArrayCollection();
        $this->boxes = new ArrayCollection();
        $this->aus = new ArrayCollection();
    }

    /**
     * Return the name of the PLN.
     *
     * @return string
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
     * Set a property of the PLN.
     *
     * @param string $key
     * @param array|string $value
     *
     * @return Pln
     */
    public function setProperty($key, $value) {
        if (in_array($key, self::LIST_REQUIRED, true)) {
            if (is_array($value)) {
                $this->properties[$key] = $value;
            } else {
                $this->properties[$key] = [$value];
            }
        } else {
            if (is_array($value) && 1 === count($value)) {
                $this->properties[$key] = $value[0];
            } else {
                $this->properties[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Get a property of the PLN.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getProperty($key) {
        if ( ! array_key_exists($key, $this->properties)) {
            return;
        }

        return $this->properties[$key];
    }

    /**
     * Remove a property from the PLN.
     *
     * @param string $key
     */
    public function removeProperty($key) : void {
        unset($this->properties[$key]);
    }

    /**
     * Add aus.
     *
     * @return Pln
     */
    public function addAu(Au $au) {
        if ( ! $this->aus->contains($au)) {
            $this->aus->add($au);
        }

        return $this;
    }

    /**
     * Remove aus.
     */
    public function removeAu(Au $au) : void {
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
     * @return Pln
     */
    public function addBox(Box $box) {
        $this->boxes[] = $box;

        return $this;
    }

    /**
     * Remove box.
     */
    public function removeBox(Box $box) : void {
        $this->boxes->removeElement($box);
    }

    /**
     * Get boxes.
     *
     * If $shuffle is true, the boxes will be returned in a random order.
     *
     * @param mixed $shuffle
     *
     * @return Box[]|Collection
     */
    public function getBoxes($shuffle = false) {
        if ($shuffle) {
            $array = $this->boxes->toArray();
            shuffle($array);

            return new ArrayCollection($array);
        }

        return $this->boxes;
    }

    /**
     * Get only the active boxes.
     *
     * If $shuffle is true, the boxes will be returned in a random order.
     *
     * @param mixed $shuffle
     *
     * @return Box[]|Collection
     */
    public function getActiveBoxes($shuffle = false) {
        $boxes = $this->boxes;
        if ($shuffle) {
            $array = $this->boxes->toArray();
            shuffle($array);
            $boxes = new ArrayCollection($array);
        }

        return $this->boxes->filter(function (Box $box) {return $box->getActive(); });
    }

    /**
     * Set keystore.
     *
     * @param string $keystore
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
     * Get the name of the keystore file.
     *
     * @return string
     */
    public function getKeystoreFilename() {
        if ($this->keystore) {
            $fileinfo = new SplFileInfo($this->keystore);

            return $fileinfo->getBasename();
        }
    }

    /**
     * Add contentProvider.
     *
     * @return Pln
     */
    public function addContentProvider(ContentProvider $contentProvider) {
        $this->contentProviders[] = $contentProvider;

        return $this;
    }

    /**
     * Remove contentProvider.
     */
    public function removeContentProvider(ContentProvider $contentProvider) : void {
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
     * Get a list of plugins for the PLN.
     *
     * @return Plugin[]
     */
    public function getPlugins() {
        $plugins = [];
        foreach ($this->contentProviders as $provider) {
            if ( ! in_array($provider->getPlugin(), $plugins, true)) {
                $plugins[] = $provider->getPlugin();
            }
        }

        return $plugins;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return Pln
     */
    public function setEmail($email) {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Get keystore.
     *
     * @return string
     */
    public function getKeystore() {
        return $this->keystore;
    }

    /**
     * Set properties.
     *
     * @return Pln
     */
    public function setProperties(array $properties) {
        $this->properties = $properties;

        return $this;
    }

    /**
     * Add aus.
     *
     * @return Pln
     */
    public function addAus(Au $aus) {
        $this->aus[] = $aus;

        return $this;
    }

    /**
     * Remove aus.
     */
    public function removeAus(Au $aus) : void {
        $this->aus->removeElement($aus);
    }
}
