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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Box.
 *
 * @ORM\Table(name="box")
 * @ORM\Entity(repositoryClass="App\Repository\BoxRepository")
 */
class Box extends AbstractEntity {
    /**
     * The DNS name.
     *
     * @var string
     *
     * @ORM\Column(name="hostname", type="string", length=255, nullable=false)
     */
    private $hostname;

    /**
     * The protocol to use in the lockss.xml file. Defaults to TCP.
     *
     * @var string
     * @ORM\Column(name="protocol", type="string", length=16, nullable=false)
     */
    private $protocol;

    /**
     * The port used for the lockss.xml file.
     *
     * @var int
     * @ORM\Column(name="port", type="integer", nullable=false)
     */
    private $port;

    /**
     * The port to use for webservice requests.
     *
     * Usually 8080, but may be different for testing.
     *
     * @var int
     * @ORM\Column(name="ws_port", type="integer", nullable=false)
     */
    private $webServicePort;

    /**
     * Protocol for the LOCKSS-UI and webservices.
     *
     * Usually http but may be https.
     *
     * @var mixed
     * @ORM\Column(name="ws_protocol", type="string", length=8, nullable=false)
     */
    private $webServiceProtocol;

    /**
     * The box's IP address.
     *
     * The class will resolve it automatically from the domain name if the
     * ipAddress is null or blank.
     *
     * @var string
     *
     * @ORM\Column(name="ip_address", type="string", length=16, nullable=false)
     * @Assert\Ip(version="4")
     */
    private $ipAddress;

    /**
     * Name of the box admin.
     *
     * @var string
     *
     * @ORM\Column(name="contact_name", type="string", length=255, nullable=true)
     */
    private $contactName;

    /**
     * Email address for the box admin.
     *
     * @var string
     *
     * @ORM\Column(name="contact_email", type="string", length=64, nullable=true)
     * @Assert\Email(
     *  strict = true
     * )
     */
    private $contactEmail;

    /**
     * Should LOM send notifications about the box to the box admin?
     *
     * If true, send the contact email a notification if the box is down or
     * otherwise unreachable.
     *
     * @var bool
     *
     * @ORM\Column(name="send_notifications", type="boolean", nullable=false, options={"default": false})
     */
    private $sendNotifications;

    /**
     * Timestamped list of box status query results.
     *
     * @var BoxStatus|Collection
     *
     * @ORM\OneToMany(targetEntity="BoxStatus", mappedBy="box", orphanRemoval=true)
     */
    private $status;

    /**
     * Is the box active and expected to communicate with LOM?
     *
     * True if the box is active. If the box is inactive, LOCKSSOMatic will
     * not attempt to interact with it. Defaults to true.
     *
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false, options={"default": true})
     */
    private $active;

    /**
     * The PLN this box is a part of.
     *
     * @var Pln
     *
     * @ORM\ManyToOne(targetEntity="Pln", inversedBy="boxes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $pln;

    /**
     * Construct the Box object.
     */
    public function __construct() {
        parent::__construct();
        $this->status = new ArrayCollection();
        $this->protocol = 'TCP';
        $this->port = 9729;
        $this->webServicePort = 8081;
        $this->webServiceProtocol = 'http';
        $this->sendNotifications = false;
        $this->active = false;
    }

    /**
     * Return the hostname or IP address.
     */
    public function __toString() : string {
        if ($this->hostname) {
            return $this->hostname;
        }

        return $this->ipAddress;
    }

    /**
     * Get the url to the UI.
     *
     * @return string
     */
    public function getUrl() {
        return "{$this->webServiceProtocol}://{$this}:{$this->webServicePort}";
    }

    /**
     * Set hostname.
     *
     * @param string $hostname
     *
     * @return Box
     */
    public function setHostname($hostname) {
        $this->hostname = $hostname;

        return $this;
    }

    /**
     * Get hostname.
     *
     * @return string
     */
    public function getHostname() {
        return $this->hostname;
    }

    /**
     * Set protocol.
     *
     * @param string $protocol
     *
     * @return Box
     */
    public function setProtocol($protocol) {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Get protocol.
     *
     * @return string
     */
    public function getProtocol() {
        return $this->protocol;
    }

    /**
     * Set port.
     *
     * @param int $port
     *
     * @return Box
     */
    public function setPort($port) {
        $this->port = $port;

        return $this;
    }

    /**
     * Get port.
     *
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * Set webServicePort.
     *
     * @param int $webServicePort
     *
     * @return Box
     */
    public function setWebServicePort($webServicePort) {
        $this->webServicePort = $webServicePort;

        return $this;
    }

    /**
     * Get webServicePort.
     *
     * @return int
     */
    public function getWebServicePort() {
        return $this->webServicePort;
    }

    /**
     * Set ipAddress.
     *
     * @param string $ipAddress
     *
     * @return Box
     */
    public function setIpAddress($ipAddress) {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress.
     *
     * @return string
     */
    public function getIpAddress() {
        return $this->ipAddress;
    }

    /**
     * Set contactName.
     *
     * @param string $contactName
     *
     * @return Box
     */
    public function setContactName($contactName) {
        $this->contactName = $contactName;

        return $this;
    }

    /**
     * Get contactName.
     *
     * @return string
     */
    public function getContactName() {
        return $this->contactName;
    }

    /**
     * Set contactEmail.
     *
     * @param string $contactEmail
     *
     * @return Box
     */
    public function setContactEmail($contactEmail) {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    /**
     * Get contactEmail.
     *
     * @return string
     */
    public function getContactEmail() {
        return $this->contactEmail;
    }

    /**
     * Set sendNotifications.
     *
     * @param bool $sendNotifications
     *
     * @return Box
     */
    public function setSendNotifications($sendNotifications) {
        $this->sendNotifications = $sendNotifications;

        return $this;
    }

    /**
     * Get sendNotifications.
     *
     * @return bool
     */
    public function getSendNotifications() {
        return $this->sendNotifications;
    }

    /**
     * Set active.
     *
     * @param bool $active
     *
     * @return Box
     */
    public function setActive($active) {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return bool
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * Set pln.
     *
     * @param Pln $pln
     *
     * @return Box
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
     * Add status.
     *
     * @return Box
     */
    public function addStatus(BoxStatus $status) {
        $this->status[] = $status;

        return $this;
    }

    /**
     * Remove status.
     */
    public function removeStatus(BoxStatus $status) : void {
        $this->status->removeElement($status);
    }

    /**
     * Get status.
     *
     * @return Collection
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set webServiceProtocol.
     *
     * @param string $webServiceProtocol
     *
     * @return Box
     */
    public function setWebServiceProtocol($webServiceProtocol) {
        $this->webServiceProtocol = $webServiceProtocol;

        return $this;
    }

    /**
     * Get webServiceProtocol.
     *
     * @return string
     */
    public function getWebServiceProtocol() {
        return $this->webServiceProtocol;
    }
}
