<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * Content
 *
 * @ORM\Table(name="content")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContentRepository")
 */
class Content extends AbstractEntity {

    /**
     * The URL for the content.
     *
     * @todo is 255 long enough?
     *
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    private $url;

    /**
     * The title of the content as deposited to LOCKSSOMatic.
     *
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * The size of the content in 1000-byte units.
     *
     * @var int
     *
     * @ORM\Column(name="size", type="integer", nullable=true)
     */
    private $size;

    /**
     * The date the deposit was recieved. Set automatically when the content
     * deposit is saved.
     *
     * @var DateTime
     *
     * @ORM\Column(name="date_deposited", type="datetime", nullable=false)
     */
    private $dateDeposited;

    /**
     * The checksum type for verifying the deposit. One of SHA1 or MD5.
     *
     * @var string
     *
     * @ORM\Column(name="checksum_type", type="string", length=24, nullable=true)
     */
    private $checksumType;

    /**
     * The value of the checksum.
     *
     * TODO should this be uppercase?
     *
     * @var string
     *
     * @ORM\Column(name="checksum_value", type="string", length=255, nullable=true)
     */
    private $checksumValue;

    /**
     * The deposit that registered this content in the database.
     *
     * @var Deposit
     *
     * @ORM\ManyToOne(targetEntity="Deposit", inversedBy="content")
     */
    private $deposit;

    /**
     * The AU this content is a part of.
     *
     * @var Au
     *
     * @ORM\ManyToOne(targetEntity="Au", inversedBy="content")
     */
    private $au;

    /**
     * The contentProperties associated with this content.
     *
     * @ORM\OneToMany(targetEntity="ContentProperty", mappedBy="content")
     *
     * @var Collection|ContentProperty[]
     */
    private $contentProperties;

    public function __toString() {
        return $this->url;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Content
     */
    public function setUrl($url) {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Content
     */
    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set size
     *
     * @param integer $size
     *
     * @return Content
     */
    public function setSize($size) {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * Set dateDeposited
     *
     * @param DateTime $dateDeposited
     *
     * @return Content
     */
    public function setDateDeposited($dateDeposited) {
        $this->dateDeposited = $dateDeposited;

        return $this;
    }

    /**
     * Get dateDeposited
     *
     * @return DateTime
     */
    public function getDateDeposited() {
        return $this->dateDeposited;
    }

    /**
     * Set checksumType
     *
     * @param string $checksumType
     *
     * @return Content
     */
    public function setChecksumType($checksumType) {
        $this->checksumType = $checksumType;

        return $this;
    }

    /**
     * Get checksumType
     *
     * @return string
     */
    public function getChecksumType() {
        return $this->checksumType;
    }

    /**
     * Set checksumValue
     *
     * @param string $checksumValue
     *
     * @return Content
     */
    public function setChecksumValue($checksumValue) {
        $this->checksumValue = $checksumValue;

        return $this;
    }

    /**
     * Get checksumValue
     *
     * @return string
     */
    public function getChecksumValue() {
        return $this->checksumValue;
    }

    /**
     * Set deposit
     *
     * @param Deposit $deposit
     *
     * @return Content
     */
    public function setDeposit(Deposit $deposit = null) {
        $this->deposit = $deposit;

        return $this;
    }

    /**
     * Get deposit
     *
     * @return Deposit
     */
    public function getDeposit() {
        return $this->deposit;
    }

    /**
     * Set au
     *
     * @param Au $au
     *
     * @return Content
     */
    public function setAu(Au $au = null) {
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
     * Add contentProperty
     *
     * @param ContentProperty $contentProperty
     *
     * @return Content
     */
    public function addContentProperty(ContentProperty $contentProperty) {
        $this->contentProperties[] = $contentProperty;

        return $this;
    }

    /**
     * Remove contentProperty
     *
     * @param ContentProperty $contentProperty
     */
    public function removeContentProperty(ContentProperty $contentProperty) {
        $this->contentProperties->removeElement($contentProperty);
    }

    /**
     * Get contentProperties
     *
     * @return Collection
     */
    public function getContentProperties() {
        return $this->contentProperties;
    }

}
