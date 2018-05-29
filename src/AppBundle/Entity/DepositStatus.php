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

/**
 * DepositStatus.
 *
 * @ORM\Table(name="deposit_status")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DepositStatusRepository")
 */
class DepositStatus extends AbstractEntity {

    /**
     * @var Deposit
     *
     * @ORM\ManyToOne(targetEntity="Deposit", inversedBy="status")
     * @ORM\JoinColumn(nullable=false)
     */
    private $deposit;

    /**
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
     *
     */
    public function __toString() {
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
     * @param array $status
     *
     * @return DepositStatus
     */
    public function setStatus($status) {
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
    public function setDeposit(Deposit $deposit = null) {
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

}
