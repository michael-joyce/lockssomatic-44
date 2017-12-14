<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * ContentProperty
 *
 * @ORM\Table(name="content_property")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContentPropertyRepository")
 */
class ContentProperty extends AbstractEntity {

    /**
     * The name of the property.
     *
     * @var string
     *
     * @ORM\Column(name="property_key", type="string", length=255, nullable=false)
     */
    private $propertyKey;

    /**
     * The value of the property. Parent properties don't have values. The value
     * may be an array.
     *
     * @var string|array
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
     * The Content for the property.
     *
     * @var Content
     *
     * @ORM\ManyToOne(targetEntity="Content", inversedBy="contentProperties")
     */
    private $content;
    
    public function __toString() {
        return $this->propertyKey;
    }

    public function __construct() {
        parent::__construct();
        $this->isList = false;
    }

    /**
     * Set propertyKey
     *
     * @param string $propertyKey
     *
     * @return ContentProperty
     */
    public function setPropertyKey($propertyKey)
    {
        $this->propertyKey = $propertyKey;

        return $this;
    }

    /**
     * Get propertyKey
     *
     * @return string
     */
    public function getPropertyKey()
    {
        return $this->propertyKey;
    }

    /**
     * Set propertyValue
     *
     * @param string|array $propertyValue
     *
     * @return ContentProperty
     */
    public function setPropertyValue($propertyValue)
    {
        if(is_array($propertyValue)) {
            $this->isList = true;
        }
        $this->propertyValue = $propertyValue;

        return $this;
    }

    /**
     * Get propertyValue
     *
     * @return string
     */
    public function getPropertyValue()
    {
        return $this->propertyValue;
    }

    /**
     * Set isList
     *
     * @param boolean $isList
     *
     * @return ContentProperty
     */
    public function setIsList($isList)
    {
        $this->isList = $isList;

        return $this;
    }

    /**
     * Get isList
     *
     * @return boolean
     */
    public function getIsList()
    {
        return $this->isList;
    }

    /**
     * Set content
     *
     * @param Content $content
     *
     * @return ContentProperty
     */
    public function setContent(Content $content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return Content
     */
    public function getContent()
    {
        return $this->content;
    }
}
