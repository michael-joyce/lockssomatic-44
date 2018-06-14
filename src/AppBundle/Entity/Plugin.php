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
 * Plugin.
 *
 * @ORM\Table(name="plugin")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PluginRepository")
 */
class Plugin extends AbstractEntity {

    const CONFIG_PROPS = 'plugin_config_props';

    const DESCR_NAME = 'org.lockss.daemon.ConfigParamDescr';

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
     * @var array
     * @ORM\Column(name="generated_params", type="array", nullable=false)
     */
    private $generatedParams;

    /**
     * AUs created for this plugin.
     *
     * @ORM\OneToMany(targetEntity="Au", mappedBy="plugin")
     *
     * @var Au[]|Collection
     */
    private $aus;

    /**
     * Content owners which use the plugin.
     *
     * @ORM\OneToMany(targetEntity="ContentProvider", mappedBy="plugin")
     *
     * @var ContentOwner[]|Collection
     */
    private $contentProviders;

    /**
     * Properties for the plugin.
     *
     * @ORM\OneToMany(targetEntity="PluginProperty", mappedBy="plugin")
     *
     * @var PluginProperty[]|Collection
     */
    private $pluginProperties;

    public function __construct() {
        parent::__construct();
        $this->generatedParams = array();
        $this->generateManifests = false;
        $this->aus = new ArrayCollection();
        $this->contentProviders = new ArrayCollection();
        $this->pluginProperties = new ArrayCollection();
    }

    public function __toString() {
        if ($this->name) {
            return $this->name;
        }
        return "";
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
        $filename = preg_replace('/-v[0-9]+\.jar$/', '.jar', $this->getFilename());
        return $filename;
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
     * @param Au $aus
     *
     * @return Plugin
     */
    public function addAus(Au $aus) {
        $this->aus[] = $aus;

        return $this;
    }

    /**
     * Remove aus.
     *
     * @param Au $aus
     */
    public function removeAus(Au $aus) {
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
     * @param ContentProvider $contentProvider
     *
     * @return Plugin
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
     * @return Collection
     */
    public function getContentProviders() {
        return $this->contentProviders;
    }

    /**
     * Add pluginProperty.
     *
     * @param PluginProperty $pluginProperty
     *
     * @return Plugin
     */
    public function addPluginProperty(PluginProperty $pluginProperty) {
        $this->pluginProperties[] = $pluginProperty;

        return $this;
    }

    /**
     * Remove pluginProperty.
     *
     * @param PluginProperty $pluginProperty
     */
    public function removePluginProperty(PluginProperty $pluginProperty) {
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
            return $p->getParent() === null;
        });
    }

    /**
     * Get a Property object.
     *
     * @param mixed $propertyKey
     *
     * @return PluginProperty|null
     */
    public function getProperty($propertyKey) {
        foreach ($this->getPluginProperties() as $property) {
            if ($property->getPropertyKey() === $propertyKey) {
                return $property;
            }
        }

        return null;
    }

    /**
     * Get a list of the configparamdescr plugin properties.
     *
     * @return PluginProperties[]
     */
    public function getPluginConfigParams() {
        $properties = array();
        foreach ($this->getPluginProperties()->toArray() as $prop) {
            /** @var PluginProperties $prop */
            if ($prop->getPropertyKey() !== self::CONFIG_PROPS) {
                continue;
            }
            foreach ($prop->getChildren() as $child) {
                if ($child->getPropertyKey() !== self::DESCR_NAME) {
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
        $properties = array();

        foreach ($this->getPluginConfigParams() as $prop) {
            $key = '';
            foreach ($prop->getChildren() as $child) {
                if ($child->getPropertyKey() === 'key') {
                    $key = $child->getPropertyValue();
                }
            }
            if ($key !== '') {
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
        $properties = array();

        foreach ($this->getPluginConfigParams() as $prop) {
            $key = '';
            $definitional = false;
            foreach ($prop->getChildren() as $child) {
                if ($child->getPropertyKey() === 'key') {
                    $key = $child->getPropertyValue();
                }
                if ($child->getPropertyKey() !== 'definitional') {
                    continue;
                }
                if ($child->getPropertyValue() === 'true') {
                    $definitional = true;
                }
            }
            if ($key !== '' && $definitional === true) {
                $properties[] = $key;
            }
        }

        return $properties;
    }

    /**
     * Convenience method. Get the plugin parameter names which are not
     * definitonal.
     *
     * @return ArrayCollection|PluginProperty[]
     */
    public function getNonDefinitionalProperties() {
        $properties = array();

        foreach ($this->getPluginConfigParams() as $prop) {
            $key = '';
            $nonDefinitional = false;
            foreach ($prop->getChildren() as $child) {
                if ($child->getPropertyKey() === 'key') {
                    $key = $child->getPropertyValue();
                }
                if ($child->getPropertyKey() !== 'definitional') {
                    continue;
                }
                if ($child->getPropertyValue() === 'false') {
                    $nonDefinitional = true;
                }
            }
            if ($key !== '' && $nonDefinitional === true) {
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
     * @param array $generatedParams
     *
     * @return Plugin
     */
    public function setGeneratedParams($generatedParams) {
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
