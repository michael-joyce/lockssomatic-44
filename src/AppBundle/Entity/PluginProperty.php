<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * PluginProperty model.
 *
 * Plugin properties are hierarchial and don't make a lot of sense.
 *
 * @ORM\Table(name="plugin_property")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PluginPropertyRepository")
 */
class PluginProperty extends AbstractEntity {
    /**
     * Name of the property, as defined by the name attribute.
     *
     * @var string
     *
     * @ORM\Column(name="property_key", type="string", length=255, nullable=false)
     */
    private $propertyKey;

    /**
     * The value of the property, if it has one.
     *
     * Properties that have children do not have values of their own.
     *
     * @var string
     *
     * @ORM\Column(name="property_value", type="text", nullable=true)
     */
    private $propertyValue;

    /**
     * True if the property value is a list/array.
     *
     * @var bool
     *
     * @ORM\Column(name="is_list", type="boolean", nullable=false)
     */
    private $isList;

    /**
     * The plugin this property describes.
     *
     * @var Plugin
     *
     * @ORM\ManyToOne(targetEntity="Plugin", inversedBy="pluginProperties")
     * @ORM\JoinColumn(nullable=false)
     */
    private $plugin;

    /**
     * The parent of the property.
     *
     * Optional.
     *
     * @var PluginProperty
     *
     * @ORM\ManyToOne(targetEntity="PluginProperty", inversedBy="children")
     */
    private $parent;

    /**
     * The child properties of this property.
     *
     * @var Collection|PluginProperty[]
     *
     * @ORM\OneToMany(targetEntity="PluginProperty", mappedBy="parent");
     */
    private $children;

    /**
     * Construct the plugin property.
     *
     * IsList defaults to false.
     */
    public function __construct() {
        parent::__construct();
        $this->isList = false;
    }

    /**
     * Return a string representation of the property.
     *
     * The representation is the property key or the empty string.
     *
     * @return string
     */
    public function __toString() {
        if ($this->propertyKey) {
            return $this->propertyKey;
        }

        return '';
    }

    /**
     * Set propertyKey.
     *
     * @param string $propertyKey
     *
     * @return PluginProperty
     */
    public function setPropertyKey($propertyKey) {
        $this->propertyKey = $propertyKey;

        return $this;
    }

    /**
     * Get propertyKey.
     *
     * @return string
     */
    public function getPropertyKey() {
        return $this->propertyKey;
    }

    /**
     * Set propertyValue, which is either a string or an array of strings.
     *
     * @param array|string $propertyValue
     *                                    Value to set.
     *
     * @return PlnProperty
     */
    public function setPropertyValue($propertyValue) {
        if (is_array($propertyValue)) {
            $this->isList = true;
            $this->propertyValue = serialize($propertyValue);
        } else {
            $this->isList = false;
            $this->propertyValue = $propertyValue;
        }

        return $this;
    }

    /**
     * Get propertyValue.
     *
     * Returns either a string or an array of strings.
     *
     * @return mixed
     */
    public function getPropertyValue() {
        if ($this->isList) {
            return unserialize($this->propertyValue);
        }

        return $this->propertyValue;
    }

    /**
     * Get isList.
     *
     * @return bool
     */
    public function isList() {
        return (bool) $this->isList;
    }

    /**
     * Set plugin.
     *
     * @param Plugin $plugin
     *
     * @return PluginProperty
     */
    public function setPlugin(Plugin $plugin = null) {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * Get plugin.
     *
     * @return Plugin
     */
    public function getPlugin() {
        return $this->plugin;
    }

    /**
     * Set parent.
     *
     * @param PluginProperty $parent
     *
     * @return PluginProperty
     */
    public function setParent(PluginProperty $parent = null) {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return PluginProperty
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Add child.
     *
     * @return PluginProperty
     */
    public function addChild(PluginProperty $child) {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child.
     *
     * @return PluginProperty
     */
    public function removeChild(PluginProperty $child) {
        $this->children->removeElement($child);

        return $this;
    }

    /**
     * Get children.
     *
     * @return Collection
     */
    public function getChildren() {
        return $this->children;
    }

    /**
     * Check if the plugin property has children.
     *
     * @return bool
     */
    public function hasChildren() {
        return $this->children && count($this->children);
    }
}
