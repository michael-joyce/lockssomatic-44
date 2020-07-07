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

/**
 * AuProperty.
 *
 * @ORM\Table(name="au_property")
 * @ORM\Entity(repositoryClass="App\Repository\AuPropertyRepository")
 */
class AuProperty extends AbstractEntity {
    /**
     * The name of the property, corresponding to the name attribute in XML.
     *
     * @var string
     *
     * @ORM\Column(name="property_key", type="string", length=255, nullable=false)
     */
    private $propertyKey;

    /**
     * The value of the property, if the property has a value.
     *
     * Properties with child properties don't have values.
     *
     * @var array|string
     *
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
     * @ORM\JoinColumn(name="au_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     */
    private $au;

    /**
     * The children of the property.
     *
     * @var AuProperty[]|Collection
     *
     * @ORM\OneToMany(targetEntity="AuProperty", mappedBy="parent")
     */
    private $children;

    public function __construct() {
        parent::__construct();
        $this->children = new ArrayCollection();
    }

    public function __toString() : string {
        return $this->propertyKey;
    }

    /**
     * Set propertyKey.
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
     * Get propertyKey.
     *
     * @return string
     */
    public function getPropertyKey() {
        return $this->propertyKey;
    }

    /**
     * Set propertyValue.
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
     * Get propertyValue.
     *
     * @return string
     */
    public function getPropertyValue() {
        return $this->propertyValue;
    }

    /**
     * Set parent.
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
     * Get parent.
     *
     * @return AuProperty
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Set au.
     *
     * @return AuProperty
     */
    public function setAu(Au $au) {
        $this->au = $au;

        return $this;
    }

    /**
     * Get au.
     *
     * @return Au
     */
    public function getAu() {
        return $this->au;
    }

    /**
     * Add child.
     *
     * @return AuProperty
     */
    public function addChild(AuProperty $child) {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child.
     */
    public function removeChild(AuProperty $child) : void {
        $this->children->removeElement($child);
    }

    /**
     * Get children.
     *
     * @return Collection
     */
    public function getChildren() {
        return $this->children;
    }

    public function hasChildren() {
        return $this->children->count() > 0;
    }
}
