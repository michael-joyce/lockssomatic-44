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
 * AuStatus.
 *
 * @ORM\Table(name="au_status")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuStatusRepository")
 */
class AuStatus extends AbstractEntity {

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="array")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="errors", type="array")
     */
    private $errors;

    /**
     * @var Au
     *
     * @ORM\ManyToOne(targetEntity="Au", inversedBy="auStatus")
     * @ORM\JoinColumn(nullable=false)
     */
    private $au;

    public function __toString() {
        return $this->created->format('c') . ' - Errors: ' . count($this->errors);
    }

    /**
     * Set status.
     *
     * @param array $status
     *
     * @return AuStatus
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
     * Set errors.
     *
     * @param array $errors
     *
     * @return AuStatus
     */
    public function setErrors($errors) {
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

    /**
     * Set au.
     *
     * @param Au $au
     *
     * @return AuStatus
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

}
