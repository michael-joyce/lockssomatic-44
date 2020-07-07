<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Nines\UserBundle\Entity\User;
use Nines\UtilBundle\Entity\AbstractEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Deposit.
 *
 * @ORM\Table(name="deposit", indexes={
 *   @ORM\Index(columns={"uuid", "url", "title"}, flags={"fulltext"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\DepositRepository")
 */
class Deposit extends AbstractEntity {
    /**
     * The UUID for the deposit.
     *
     * Should be UPPERCASE.
     *
     * @var string
     *
     * @Assert\Uuid(versions = {"Uuid:V4_RANDOM"}, strict=false)
     * @ORM\Column(name="uuid", type="string", length=36, nullable=false)
     */
    private $uuid;

    /**
     * The URL for the content.
     *
     * @var string
     *
     * @todo is 255 long enough?
     *
     * @Assert\Url()
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    private $url;

    /**
     * The title of the deposit.
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
     * The amount of agreement for the deposit's content URLs in the lockss boxes.
     *
     * @var float
     *
     * @ORM\Column(name="agreement", type="float", nullable=true)
     */
    private $agreement;

    /**
     * The date the deposit's status was last checked.
     *
     * @var DateTime
     * @ORM\Column(name="checked", type="datetime", nullable=true)
     */
    private $checked;

    /**
     * A summary/description of the deposit.
     *
     * @var string
     *
     * @ORM\Column(name="summary", type="string", length=255, nullable=true)
     */
    private $summary;

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
     * The content provider that created the deposit.s.
     *
     * @var ContentProvider
     *
     * @ORM\ManyToOne(targetEntity="ContentProvider", inversedBy="deposits")
     * @ORM\JoinColumn(nullable=false)
     */
    private $contentProvider;

    /**
     * The (optional) user making the deposit, perhaps via the gui.
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Nines\UserBundle\Entity\User")
     */
    private $user;

    /**
     * The statuses from LOCKSS for the deposit.
     *
     * @var DepositStatus
     *
     * @ORM\OneToMany(targetEntity="DepositStatus", mappedBy="deposit")
     */
    private $status;

    /**
     * The AU this content is a part of.
     *
     * @var Au
     *
     * @ORM\ManyToOne(targetEntity="Au", inversedBy="deposits")
     * @ORM\JoinColumn(nullable=false)
     */
    private $au;

    /**
     * Get the deposit title.
     */
    public function __toString() : string {
        return $this->title;
    }

    /**
     * Set uuid.
     *
     * @param string $uuid
     *
     * @return Deposit
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
     * Set url.
     *
     * @param string $url
     *
     * @return Deposit
     */
    public function setUrl($url) {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    public function getFilename() {
        return basename($this->url);
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Deposit
     */
    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set size.
     *
     * @param int $size
     *
     * @return Deposit
     */
    public function setSize($size) {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * Set agreement.
     *
     * @param float $agreement
     *
     * @return Deposit
     */
    public function setAgreement($agreement) {
        $this->agreement = $agreement;

        return $this;
    }

    /**
     * Get agreement.
     *
     * @return float
     */
    public function getAgreement() {
        return $this->agreement;
    }

    /**
     * @return DateTime
     */
    public function getChecked() {
        return $this->checked;
    }

    /**
     * @param $checked
     *
     * @throws Exception
     */
    public function setChecked(DateTime $checked = null) : void {
        if ( ! $checked) {
            $checked = new DateTime();
        }
        $this->checked = $checked;
    }

    /**
     * Set summary.
     *
     * @param string $summary
     *
     * @return Deposit
     */
    public function setSummary($summary) {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get summary.
     *
     * @return string
     */
    public function getSummary() {
        return $this->summary;
    }

    /**
     * Set checksumType.
     *
     * @param string $checksumType
     *
     * @return Deposit
     */
    public function setChecksumType($checksumType) {
        $this->checksumType = $checksumType;

        return $this;
    }

    /**
     * Get checksumType.
     *
     * @return string
     */
    public function getChecksumType() {
        return $this->checksumType;
    }

    /**
     * Set checksumValue.
     *
     * @param string $checksumValue
     *
     * @return Deposit
     */
    public function setChecksumValue($checksumValue) {
        $this->checksumValue = strtoupper($checksumValue);

        return $this;
    }

    /**
     * Get checksumValue.
     *
     * @return string
     */
    public function getChecksumValue() {
        return $this->checksumValue;
    }

    /**
     * Get dateDeposited.
     *
     * @return DateTime
     */
    public function getDateDeposited() {
        return $this->created;
    }

    /**
     * Set contentProvider.
     *
     * @param ContentProvider $contentProvider
     *
     * @return Deposit
     */
    public function setContentProvider(ContentProvider $contentProvider = null) {
        $this->contentProvider = $contentProvider;

        return $this;
    }

    /**
     * Get contentProvider.
     *
     * @return DepositProvider
     */
    public function getContentProvider() {
        return $this->contentProvider;
    }

    /**
     * Set au.
     *
     * @param Au $au
     *
     * @return Deposit
     */
    public function setAu(Au $au = null) {
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

    public function getPlugin() {
        if ($this->au && $this->au->getPlugin()) {
            return $this->au->getPlugin();
        }

        return $this->contentProvider->getPlugin();
    }

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return Deposit
     */
    public function setUser(User $user = null) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Add status.
     *
     * @return Deposit
     */
    public function addStatus(DepositStatus $status) {
        $this->status[] = $status;

        return $this;
    }

    /**
     * Remove status.
     */
    public function removeStatus(DepositStatus $status) : void {
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
     * Add contentProperty.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return Deposit
     */
    public function setProperty($key, $value) {
        $this->properties[$key] = $value;

        return $this;
    }

    public function setProperties(array $properties) {
        $this->properties = $properties;

        return $this;
    }

    public function getProperties() {
        return array_keys($this->properties);
    }

    /**
     * Get the value of a content property, optionally encoded to LOCKSS standards.
     *
     * @param string $key
     *
     * @return string
     */
    public function getProperty($key) {
        if ($this->hasProperty($key)) {
            return $this->properties[$key];
        }
    }

    public function hasProperty($key) {
        return isset($this->properties[$key]);
    }
}
