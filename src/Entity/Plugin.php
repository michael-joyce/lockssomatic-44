<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;
use SplFileInfo;

/**
 * Plugin.
 *
 * @ORM\Table(name="plugin")
 * @ORM\Entity(repositoryClass="App\Repository\PluginRepository")
 */
class Plugin extends AbstractEntity {
    /**
     * Name of the XML element that defines the plugin properties.
     */
    public const CONFIG_PROPS = 'plugin_config_props';

    /**
     * Name of the XML element that defines the configuration parameters.
     */
    public const DESCR_NAME = 'org.lockss.daemon.ConfigParamDescr';

    /**
     * Name of the plugin.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128)
     */
    private $name;

    /**
     * Path, in the local file system, to the plugin file.
     *
     * @var string
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    private $path;

    /**
     * Version number for the plugin, from the plugin's Xml config.
     *
     * @var int
     * @ORM\Column(name="version", type="integer")
     */
    private $version;

    /**
     * Plugin identifier (an FQDN) from the plugin's Xml config.
     *
     * @var string
     * @ORM\Column(name="identifier", type="string", length=255)
     */
    private $identifier;

    /**
     * If true, LOCKSSOMatic will generate manifest files for this plugin's AUs.
     *
     * @var bool
     * @ORM\Column(name="generate_manifests", type="boolean", nullable=false)
     */
    private $generateManifests;

    /**
     * List of LOM-generated parameters.
     *
     * @var array
     * @ORM\Column(name="generated_params", type="array", nullable=false)
     */
    private $generatedParams;

    /**
     * AUs created for this plugin.
     *
     * @var Au[]|Collection
     * @ORM\OneToMany(targetEntity="Au", mappedBy="plugin")
     */
    private $aus;

    /**
     * Content owners which use the plugin.
     *
     * @var Collection|ContentOwner[]
     * @ORM\OneToMany(targetEntity="ContentProvider", mappedBy="plugin")
     */
    private $contentProviders;

    /**
     * Properties for the plugin.
     *
     * @var Collection|PluginProperty[]
     * @ORM\OneToMany(targetEntity="PluginProperty", mappedBy="plugin", cascade={"persist"})
     */
    private $pluginProperties;

    /**
     * Build the plugin.
     */
    public function __construct() {
        parent::__construct();
        $this->generatedParams = [];
        $this->generateManifests = false;
        $this->aus = new ArrayCollection();
        $this->contentProviders = new ArrayCollection();
        $this->pluginProperties = new ArrayCollection();
    }

    /**
     * Get the name of the plugin or the empty string if it does not have one.
     *
     * @return string
     */
    public function __toString() : string {
        if ($this->name) {
            return $this->name;
        }

        return '';
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Plugin
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
     * Set path.
     *
     * @param string $path
     *
     * @return Plugin
     */
    public function setPath($path) {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename() {
        $fileinfo = new SplFileInfo($this->path);

        return $fileinfo->getBasename();
    }

    public function getOriginalFilename() {
        return preg_replace('/-v[0-9]+\.jar$/', '.jar', $this->getFilename());
    }

    /**
     * Set version.
     *
     * @param int $version
     *
     * @return Plugin
     */
    public function setVersion($version) {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return int
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * Set identifier.
     *
     * @param string $identifier
     *
     * @return Plugin
     */
    public function setIdentifier($identifier) {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier.
     *
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * Add aus.
     *
     * @return Plugin
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

    /**
     * Get aus.
     *
     * @return Collection
     */
    public function getAus() {
        return $this->aus;
    }

    /**
     * Add contentProvider.
     *
     * @return Plugin
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
     * @return Collection
     */
    public function getContentProviders() {
        return $this->contentProviders;
    }

    /**
     * Add pluginProperty.
     *
     * @return Plugin
     */
    public function addPluginProperty(PluginProperty $pluginProperty) {
        $this->pluginProperties[] = $pluginProperty;

        return $this;
    }

    /**
     * Remove pluginProperty.
     */
    public function removePluginProperty(PluginProperty $pluginProperty) : void {
        $this->pluginProperties->removeElement($pluginProperty);
    }

    /**
     * Get pluginProperties.
     *
     * @return Collection
     */
    public function getPluginProperties() {
        return $this->pluginProperties;
    }

    public function getRootPluginProperties() {
        return $this->pluginProperties->filter(function (PluginProperty $p) {
            return null === $p->getParent();
        });
    }

    /**
     * Get a Property object.
     *
     * @param mixed $propertyKey
     *
     * @return null|PluginProperty
     */
    public function getProperty($propertyKey) {
        foreach ($this->getPluginProperties() as $property) {
            if ($property->getPropertyKey() === $propertyKey) {
                return $property;
            }
        }
    }

    /**
     * Get a list of the configparamdescr plugin properties.
     *
     * @return PluginProperties[]
     */
    public function getPluginConfigParams() {
        $properties = [];
        foreach ($this->getPluginProperties()->toArray() as $prop) {
            /** @var PluginProperties $prop */
            if (self::CONFIG_PROPS !== $prop->getPropertyKey()) {
                continue;
            }
            foreach ($prop->getChildren() as $child) {
                if (self::DESCR_NAME !== $child->getPropertyKey()) {
                    continue;
                }
                $properties[] = $child;
            }
        }

        return $properties;
    }

    /**
     * Get the definitional plugin parameter names.
     *
     * @return ArrayCollection|PluginProperty[]
     */
    public function getConfigPropertyNames() {
        $properties = [];

        foreach ($this->getPluginConfigParams() as $prop) {
            $key = '';
            foreach ($prop->getChildren() as $child) {
                if ('key' === $child->getPropertyKey()) {
                    $key = $child->getPropertyValue();
                }
            }
            if ('' !== $key) {
                $properties[] = $key;
            }
        }

        return $properties;
    }

    /**
     * Get the definitional plugin parameter names.
     *
     * @return ArrayCollection|PluginProperty[]
     */
    public function getDefinitionalPropertyNames() {
        $properties = [];

        foreach ($this->getPluginConfigParams() as $prop) {
            $key = '';
            $definitional = false;
            foreach ($prop->getChildren() as $child) {
                if ('key' === $child->getPropertyKey()) {
                    $key = $child->getPropertyValue();
                }
                if ('definitional' !== $child->getPropertyKey()) {
                    continue;
                }
                if ('true' === $child->getPropertyValue()) {
                    $definitional = true;
                }
            }
            if ('' !== $key && true === $definitional) {
                $properties[] = $key;
            }
        }

        return $properties;
    }

    /**
     * Get the plugin parameter names which are not definitonal.
     *
     * @return ArrayCollection|PluginProperty[]
     */
    public function getNonDefinitionalProperties() {
        $properties = [];

        foreach ($this->getPluginConfigParams() as $prop) {
            $key = '';
            $nonDefinitional = false;
            foreach ($prop->getChildren() as $child) {
                if ('key' === $child->getPropertyKey()) {
                    $key = $child->getPropertyValue();
                }
                if ('definitional' !== $child->getPropertyKey()) {
                    continue;
                }
                if ('false' === $child->getPropertyValue()) {
                    $nonDefinitional = true;
                }
            }
            if ('' !== $key && true === $nonDefinitional) {
                $properties[] = $key;
            }
        }

        return $properties;
    }

    /**
     * Set generateManifests.
     *
     * @param bool $generateManifests
     *
     * @return Plugin
     */
    public function setGenerateManifests($generateManifests) {
        $this->generateManifests = $generateManifests;

        return $this;
    }

    /**
     * Get generateManifests.
     *
     * @return bool
     */
    public function getGenerateManifests() {
        return $this->generateManifests;
    }

    /**
     * Set generatedParams.
     *
     * @return Plugin
     */
    public function setGeneratedParams(array $generatedParams) {
        $this->generatedParams = $generatedParams;

        return $this;
    }

    /**
     * Get generatedParams.
     *
     * @return array
     */
    public function getGeneratedParams() {
        return $this->generatedParams;
    }
}
