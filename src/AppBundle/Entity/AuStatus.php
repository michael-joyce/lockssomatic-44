<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Entity;

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
     * Status data as returned from LOCKSS.
     *
     * @var string
     *
     * @ORM\Column(name="status", type="array")
     */
    private $status;

    /**
     * List fo errors during status check.
     *
     * @var string
     *
     * @ORM\Column(name="errors", type="array")
     */
    private $errors;

    /**
     * Archival unit for the status.
     *
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
     * @return AuStatus
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
     * Set errors.
     *
     * @return AuStatus
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
