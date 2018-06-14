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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ContentProvider.
 *
 * @ORM\Table(name="content_provider")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContentProviderRepository")
 */
class ContentProvider extends AbstractEntity {

    /**
     * The UUID for the provider. SWORD requests must include this UUID in the
     * On-Behalf-Of header or in the URL.
     *
     * @var string
     *
     * @ORM\Column(name="uuid", type="string", length=36, nullable=false)
     * @Assert\Uuid(versions = {"Uuid:V4_RANDOM"}, strict=false)
     */
    private $uuid;

    /**
     * LOCKSS permission URL for the provider. Must be on the same host
     * as the content.
     *
     * @var string
     *
     * @Assert\Url()
     * @ORM\Column(name="permissionUrl", type="string", length=255, nullable=false)
     */
    private $permissionurl;

    /**
     * Name of the provider.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * The maximum file size for the provider in 1000-byte units.
     *
     * @var int
     *
     * @ORM\Column(name="max_file_size", type="integer", nullable=true)
     */
    private $maxFileSize;

    /**
     * The maximum AU size for the provider in 1000-byte units.
     *
     * @var int
     *
     * @ORM\Column(name="max_au_size", type="integer", nullable=true)
     */
    private $maxAuSize;

    /**
     * The owner for the provider. Providers make deposit on behalf
     * of owners.
     *
     * @var ContentOwner
     *
     * @ORM\ManyToOne(targetEntity="ContentOwner", inversedBy="contentProviders")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="content_owner_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $contentOwner;

    /**
     * PLN for the provider.
     *
     * @var Pln
     *
     * @ORM\ManyToOne(targetEntity="Pln", inversedBy="contentProviders")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="pln_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $pln;

    /**
     * The LOCKSS Plugin for the content owner.
     *
     * @var Plugin
     *
     * @ORM\ManyToOne(targetEntity="Plugin", inversedBy="contentProviders")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="plugin_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $plugin;

    /**
     * List of AUs for the provider.
     *
     * @ORM\OneToMany(targetEntity="Au", mappedBy="contentProvider")
     *
     * @var Au[]|Collection
     */
    private $aus;

    /**
     * Deposits made by the provider.
     *
     * @ORM\OneToMany(targetEntity="Deposit", mappedBy="contentProvider")
     *
     * @var Collection|Deposit[]
     */
    private $deposits;

    public function __toString() {
        return $this->name;
    }

    /**
     * Set uuid.
     *
     * @param string $uuid
     *
     * @return ContentProvider
     */
    public function setUuid($uuid) {
        $this->uuid = strtoupper($uuid);

        return $this;
    }

    /**
     * Get uuid.
     *
     * @return string
     */
    public function getUuid() {
        return $this->uuid;
    }

    /**
     * Set permissionurl.
     *
     * @param string $permissionurl
     *
     * @return ContentProvider
     */
    public function setPermissionUrl($permissionurl) {
        $this->permissionurl = $permissionurl;

        return $this;
    }

    /**
     * Get permissionurl.
     *
     * @return string
     */
    public function getPermissionUrl() {
        return $this->permissionurl;
    }

    public function getPermissionHost() {
        return parse_url($this->getPermissionUrl(), PHP_URL_HOST);
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return ContentProvider
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
     * Set maxFileSize.
     *
     * @param int $maxFileSize
     *
     * @return ContentProvider
     */
    public function setMaxFileSize($maxFileSize) {
        $this->maxFileSize = $maxFileSize;

        return $this;
    }

    /**
     * Get maxFileSize.
     *
     * @return int
     */
    public function getMaxFileSize() {
        return $this->maxFileSize;
    }

    /**
     * Set maxAuSize.
     *
     * @param int $maxAuSize
     *
     * @return ContentProvider
     */
    public function setMaxAuSize($maxAuSize) {
        $this->maxAuSize = $maxAuSize;

        return $this;
    }

    /**
     * Get maxAuSize.
     *
     * @return int
     */
    public function getMaxAuSize() {
        return $this->maxAuSize;
    }

    /**
     * Set contentOwner.
     *
     * @param ContentOwner $contentOwner
     *
     * @return ContentProvider
     */
    public function setContentOwner(ContentOwner $contentOwner = null) {
        $this->contentOwner = $contentOwner;

        return $this;
    }

    /**
     * Get contentOwner.
     *
     * @return ContentOwner
     */
    public function getContentOwner() {
        return $this->contentOwner;
    }

    /**
     * Set pln.
     *
     * @param Pln $pln
     *
     * @return ContentProvider
     */
    public function setPln(Pln $pln = null) {
        $this->pln = $pln;

        return $this;
    }

    /**
     * Get pln.
     *
     * @return Pln
     */
    public function getPln() {
        return $this->pln;
    }

    /**
     * Set plugin.
     *
     * @param Plugin $plugin
     *
     * @return ContentProvider
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
     * Add aus.
     *
     * @param Au $aus
     *
     * @return ContentProvider
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

    public function setAus($aus) {
        $this->aus = $aus;
    }

    /**
     * Add deposit.
     *
     * @param Deposit $deposit
     *
     * @return ContentProvider
     */
    public function addDeposit(Deposit $deposit) {
        $this->deposits[] = $deposit;

        return $this;
    }

    /**
     * Remove deposit.
     *
     * @param Deposit $deposit
     */
    public function removeDeposit(Deposit $deposit) {
        $this->deposits->removeElement($deposit);
    }

    /**
     * Get deposits.
     *
     * @return Collection
     */
    public function getDeposits() {
        return $this->deposits;
    }

}
