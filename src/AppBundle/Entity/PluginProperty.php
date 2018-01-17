<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * PluginProperty
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
     * The value of the property, if it has one. Properties that have children
     * do not have values of their own.
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
     */
    private $plugin;

    /**
     * The parent of the property.
     *
     * @var PluginProperty
     *
     * @ORM\ManyToOne(targetEntity="PluginProperty", inversedBy="children")
     */
    private $parent;

    /**
     * The child properties of this property.
     *
     * @ORM\OneToMany(targetEntity="PluginProperty", mappedBy="parent");
     *
     * @var PluginProperty[]|Collection
     */
    private $children;

    public function __construct() {
        parent::__construct();
        $this->isList = false;
    }

    public function __toString() {
        if ($this->propertyKey) {
            return $this->propertyKey;
        }
        return "";
    }

    /**
     * Set propertyKey
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
     * Get propertyKey
     *
     * @return string
     */
    public function getPropertyKey() {
        return $this->propertyKey;
    }

    /**
     * Set propertyValue, which is either a string or an array of strings.
     *
     * @param string|array $propertyValue
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
     * Get propertyValue. Returns either a string or an array of strings.
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
     * Get isList
     *
     * @return boolean
     */
    public function isList() {
        return (bool) $this->isList;
    }

    /**
     * Set plugin
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
     * Get plugin
     *
     * @return Plugin
     */
    public function getPlugin() {
        return $this->plugin;
    }

    /**
     * Set parent
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
     * Get parent
     *
     * @return PluginProperty
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Add child
     *
     * @param PluginProperty $child
     *
     * @return PluginProperty
     */
    public function addChild(PluginProperty $child) {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param PluginProperty $child
     */
    public function removeChild(PluginProperty $child) {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return Collection
     */
    public function getChildren() {
        return $this->children;
    }
    
    public function hasChildren() {
        return ($this->children && count($this->children));
    }

}
