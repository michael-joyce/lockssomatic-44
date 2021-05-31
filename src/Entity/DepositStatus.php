<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * DepositStatus.
 *
 * @ORM\Table(name="deposit_status")
 * @ORM\Entity(repositoryClass="App\Repository\DepositStatusRepository")
 */
class DepositStatus extends AbstractEntity {
    /**
     * Deposit that owns this status.
     *
     * @var Deposit
     *
     * @ORM\ManyToOne(targetEntity="Deposit", inversedBy="status")
     * @ORM\JoinColumn(nullable=false)
     */
    private $deposit;

    /**
     * Agreement level, between 0 (no agreement) and 1 (complete agreement).
     *
     * @var float
     * @ORM\Column(name="agreement", type="float")
     */
    private $agreement;

    /**
     * A deposit status is a big array.
     *
     * @var array
     *
     * @ORM\Column(name="status", type="array", nullable=true)
     */
    private $status;

    /**
     * List of errors during status check.
     *
     * @var type
     *
     * @ORM\Column(name="errors", type="array", nullable=true)
     */
    private $errors;

    /**
     * Build the deposit status object.
     */
    public function __construct() {
        parent::__construct();
        $this->errors = [];
    }

    /**
     * Return the deposit identifier and date of this status check.
     */
    public function __toString() : string {
        return $this->deposit . ' ' . $this->created->format('c');
    }

    /**
     * Set agreement.
     *
     * @param float $agreement
     *
     * @return DepositStatus
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
     * Set status.
     *
     * @return DepositStatus
     */
    public function setStatus(array $status) {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return array
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set deposit.
     *
     * @param Deposit $deposit
     *
     * @return DepositStatus
     */
    public function setDeposit(?Deposit $deposit = null) {
        $this->deposit = $deposit;

        return $this;
    }

    /**
     * Get deposit.
     *
     * @return Deposit
     */
    public function getDeposit() {
        return $this->deposit;
    }

    /**
     * Set errors.
     *
     * @return DepositStatus
     */
    public function setErrors(array $errors) {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Get errors.
     *
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
}
