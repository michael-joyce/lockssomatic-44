<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * AuStatus
 *
 * @ORM\Table(name="au_status")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuStatusRepository")
 */
class AuStatus extends AbstractEntity {

    /**
     * @var DateTime
     *
     * @ORM\Column(name="query_date", type="datetime", nullable=false)
     */
    private $queryDate;

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
     */
    private $au;

    public function __toString() {
        return $this->queryDate->format('c') . ' - Errors: ' . count($this->errors);
    }

    /**
     * Set queryDate
     *
     * @param \DateTime $queryDate
     *
     * @return AuStatus
     */
    public function setQueryDate($queryDate) {
        $this->queryDate = $queryDate;

        return $this;
    }

    /**
     * Get queryDate
     *
     * @return \DateTime
     */
    public function getQueryDate() {
        return $this->queryDate;
    }

    /**
     * Set status
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
     * Get status
     *
     * @return array
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set errors
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
     * Get errors
     *
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Set au
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
     * Get au
     *
     * @return Au
     */
    public function getAu() {
        return $this->au;
    }

}
