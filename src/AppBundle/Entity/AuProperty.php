<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * AuProperty
 *
 * @ORM\Table(name="au_property")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuPropertyRepository")
 */
class AuProperty extends AbstractEntity {

    /**
     * The name of the property, corresponding to the name attribute in XML.
     *
     * @var string
     * @ORM\Column(name="property_key", type="string", length=255, nullable=false)
     */
    private $propertyKey;

    /**
     * The value of the property, if the property has a value. Properties with
     * child properties don't have values.
     *
     * @var string|array
     * @ORM\Column(name="property_value", type="text", nullable=true)
     */
    private $propertyValue;

    /**
     * The parent of the property, if it has one.
     *
     * @var AuProperty
     *
     * @ORM\ManyToOne(targetEntity="AuProperty", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $parent;

    /**
     * The AU for the property.
     *
     * @var Au
     *
     * @ORM\ManyToOne(targetEntity="Au", inversedBy="auProperties")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="au_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     */
    private $au;

    /**
     * The children of the property.
     *
     * @ORM\OneToMany(targetEntity="AuProperty", mappedBy="parent")
     *
     * @var Collection|AuProperty[]
     */
    private $children;

    public function __toString() {
        return $this->propertyKey;
    }

    /**
     * Set propertyKey
     *
     * @param string $propertyKey
     *
     * @return AuProperty
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
     * Set propertyValue
     *
     * @param string $propertyValue
     *
     * @return AuProperty
     */
    public function setPropertyValue($propertyValue) {
        $this->propertyValue = $propertyValue;

        return $this;
    }

    /**
     * Get propertyValue
     *
     * @return string
     */
    public function getPropertyValue() {
        return $this->propertyValue;
    }

    /**
     * Set parent
     *
     * @param AuProperty $parent
     *
     * @return AuProperty
     */
    public function setParent(AuProperty $parent = null) {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return AuProperty
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Set au
     *
     * @param Au $au
     *
     * @return AuProperty
     */
    public function setAu(Au $au) {
        $this->au = $au;

        return $this;
    }

    /**
     * Get au
     *
     * @return Au
     */
    public function getAu() {
        return $this->au;
    }

    /**
     * Add child
     *
     * @param AuProperty $child
     *
     * @return AuProperty
     */
    public function addChild(AuProperty $child) {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param AuProperty $child
     */
    public function removeChild(AuProperty $child) {
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

}
