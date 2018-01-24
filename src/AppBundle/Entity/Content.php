<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @Assert\URL()
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
     * @var string
     *
     * @ORM\Column(name="checksum_value", type="string", length=255, nullable=true)
     */
    private $checksumValue;
    
    /**
     * Key/value array of content properties.
     *
     * @var array
     * @ORM\Column(name="properties", type="array", nullable=false)
     */
    private $properties;

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

    public function __construct() {
        parent::__construct();
        $this->properties = array();
    }

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
     * @return Content
     */
    public function setDateDeposited() {
        if ($this->dateDeposited === null) {
            $this->dateDeposited = new DateTime();
        }

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
        $this->checksumValue = strtoupper($checksumValue);

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
    
    public function getPlugin() {
        if($this->au && $this->au->getPlugin()) {
            return $this->au->getPlugin();
        }
        if($this->deposit 
                && $this->deposit->getContentProvider() 
                && $this->deposit->getContentProvider()->getPlugin()) {
            return $this->au->getPlugin();
        }
        return null;
    }

    /**
     * Add contentProperty
     *
     * @param string $key
     * @param mixed $value
     *
     * @return Content
     */
    public function setProperty($key, $value) {
        $this->properties[$key] = $value;

        return $this;
    }

    public function getProperties() {
        return array_keys($this->properties);
    }

    /**
     * Get the value of a content property, optionally encoded to
     * LOCKSS standards.
     *
     * @param string $key
     * @param bool $encoded
     * @return string
     */
    public function getProperty($key) {
        return $this->properties[$key];
    }
    
    public function hasProperty($key) {
        return isset($this->properties[$key]);
    }

}
