<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
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
     * @var PluginProperty[]|Collection
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
     *   Stringish representation of the property.
     */
    public function __toString() {
        if ($this->propertyKey) {
            return $this->propertyKey;
        }
        return "";
    }

    /**
     * Set propertyKey.
     *
     * @param string $propertyKey
     *   Property key to set.
     *
     * @return PluginProperty
     *   $this for a literate programming experience.
     */
    public function setPropertyKey($propertyKey) {
        $this->propertyKey = $propertyKey;

        return $this;
    }

    /**
     * Get propertyKey.
     *
     * @return string
     *   Name of the property.
     */
    public function getPropertyKey() {
        return $this->propertyKey;
    }

    /**
     * Set propertyValue, which is either a string or an array of strings.
     *
     * @param string|array $propertyValue
     *   Value to set.
     *
     * @return PlnProperty
     *   $this for a literate programming experience.
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
     *   An array or string.
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
     *   True if the property is a list of values.
     */
    public function isList() {
        return (bool) $this->isList;
    }

    /**
     * Set plugin.
     *
     * @param Plugin $plugin
     *   Assign the property to this plugin.
     *
     * @return PluginProperty
     *   $this for a literate programming experience.
     */
    public function setPlugin(Plugin $plugin = null) {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * Get plugin.
     *
     * @return Plugin
     *   The plugin associated with this property.
     */
    public function getPlugin() {
        return $this->plugin;
    }

    /**
     * Set parent.
     *
     * @param PluginProperty $parent
     *   New parent for the property.
     *
     * @return PluginProperty
     *   $this for a literate programming experience.
     */
    public function setParent(PluginProperty $parent = null) {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return PluginProperty
     *   Parent property.
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Add child.
     *
     * @param PluginProperty $child
     *   Property to add.
     *
     * @return PluginProperty
     *   $this for a literate programming experience.
     */
    public function addChild(PluginProperty $child) {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child.
     *
     * @param PluginProperty $child
     *   Property to remove.
     *
     * @return PluginProperty
     *   $this for a literate programming experience.
     */
    public function removeChild(PluginProperty $child) {
        $this->children->removeElement($child);
        return $this;
    }

    /**
     * Get children.
     *
     * @return Collection
     *   The children of the plugin.
     */
    public function getChildren() {
        return $this->children;
    }
    
    /**
     * Check if the plugin property has children.
     *
     * @return bool
     *   True if the plugin has children.
     */
    public function hasChildren() {
        return ($this->children && count($this->children));
    }

}
