<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nines\UserBundle\Entity\User;

/**
 * Deposit
 *
 * @ORM\Table(name="deposit")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DepositRepository")
 */
class Deposit extends \Nines\UtilBundle\Entity\AbstractEntity {

    /**
     * The UUID for the deposit. Should be UPPERCASE.
     *
     * @var string
     *
     * @ORM\Column(name="uuid", type="string", length=36, nullable=false, unique=true)
     */
    private $uuid;

    /**
     * The title of the deposit.
     *
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * The amount of agreement for the deposit's content URLs in the lockss boxes.
     *
     * @var float
     *
     * @ORM\Column(name="agreement", type="float", nullable=true)
     */
    private $agreement;

    /**
     * A summary/description of the deposit.
     *
     * @var string
     *
     * @ORM\Column(name="summary", type="string", length=255, nullable=true)
     */
    private $summary;

    /**
     * The date LOCKSSOMatic recieved the deposit.
     *
     * @var DateTime
     *
     * @ORM\Column(name="date_deposited", type="datetime", nullable=false)
     */
    private $dateDeposited;

    /**
     * The content provider that created the deposit.s.
     *
     * @var ContentProvider
     *
     * @ORM\ManyToOne(targetEntity="ContentProvider", inversedBy="deposits")
     */
    private $contentProvider;

    /**
     * The (optional) user making the deposit, perhaps via the gui.
     *
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User
     */
    private $user;

    /**
     * The content for the deposit.
     *
     * @ORM\OneToMany(targetEntity="Content", mappedBy="deposit")
     * 
     * @var Content[]|Collection
     */
    private $content;

    /**
     * The statuses from LOCKSS for the deposit.
     *
     * @var DepositStatus
     *
     * @ORM\OneToMany(targetEntity="DepositStatus", mappedBy="deposit")
     */
    private $status;

    public function __toString() {
        return $this->title;
    }

    /**
     * Set uuid
     *
     * @param string $uuid
     *
     * @return Deposit
     */
    public function setUuid($uuid) {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function getUuid() {
        return $this->uuid;
    }

    /**
     * Set title
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
     * Get title
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set agreement
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
     * Get agreement
     *
     * @return float
     */
    public function getAgreement() {
        return $this->agreement;
    }

    /**
     * Set summary
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
     * Get summary
     *
     * @return string
     */
    public function getSummary() {
        return $this->summary;
    }

    /**
     * Set dateDeposited
     *
     * @param DateTime $dateDeposited
     *
     * @return Deposit
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
     * Set contentProvider
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
     * Get contentProvider
     *
     * @return ContentProvider
     */
    public function getContentProvider() {
        return $this->contentProvider;
    }

    /**
     * Set user
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
     * Get user
     *
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Add content
     *
     * @param Content $content
     *
     * @return Deposit
     */
    public function addContent(Content $content) {
        $this->content[] = $content;

        return $this;
    }

    /**
     * Remove content
     *
     * @param Content $content
     */
    public function removeContent(Content $content) {
        $this->content->removeElement($content);
    }

    /**
     * Get content
     *
     * @return Collection
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Add status
     *
     * @param DepositStatus $status
     *
     * @return Deposit
     */
    public function addStatus(DepositStatus $status) {
        $this->status[] = $status;

        return $this;
    }

    /**
     * Remove status
     *
     * @param DepositStatus $status
     */
    public function removeStatus(DepositStatus $status) {
        $this->status->removeElement($status);
    }

    /**
     * Get status
     *
     * @return Collection
     */
    public function getStatus() {
        return $this->status;
    }

}
