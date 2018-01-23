<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * BoxStatus
 *
 * @ORM\Table(name="box_status")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BoxStatusRepository")
 */
class BoxStatus extends AbstractEntity {

    /**
     * @var Box
     *
     * @ORM\ManyToOne(targetEntity="Box", inversedBy="status")
     */
    private $box;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="query_date", type="datetime", nullable=false)
     */
    private $queryDate;

    /**
     * @var bool
     *
     * @ORM\Column(name="success", type="boolean")
     */
    private $success;

    /**
     * @var string
     *
     * @ORM\Column(name="errors", type="text", nullable=true)
     */
    private $errors;

    /**
     * @var Collection|CacheStatus
     *
     * @ORM\OneToMany(targetEntity="CacheStatus", mappedBy="boxStatus", orphanRemoval=true)
     */
    private $caches;

    public function __toString() {
        return $this->box . " " . $this->queryDate->format('c');
    }

    /**
     * Set queryDate
     *
     * @param DateTime $queryDate
     *
     * @return BoxStatus
     */
    public function setQueryDate($queryDate) {
        $this->queryDate = $queryDate;

        return $this;
    }

    /**
     * Get queryDate
     *
     * @return DateTime
     */
    public function getQueryDate() {
        return $this->queryDate;
    }

    /**
     * Set success
     *
     * @param boolean $success
     *
     * @return BoxStatus
     */
    public function setSuccess($success) {
        $this->success = $success;

        return $this;
    }

    /**
     * Get success
     *
     * @return boolean
     */
    public function getSuccess() {
        return $this->success;
    }

    /**
     * Set errors
     *
     * @param string $errors
     *
     * @return BoxStatus
     */
    public function setErrors($errors) {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Get errors
     *
     * @return string
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Set box
     *
     * @param Box $box
     *
     * @return BoxStatus
     */
    public function setBox(Box $box = null) {
        $this->box = $box;

        return $this;
    }

    /**
     * Get box
     *
     * @return Box
     */
    public function getBox() {
        return $this->box;
    }

    /**
     * Add cach
     *
     * @param CacheStatus $cach
     *
     * @return BoxStatus
     */
    public function addCach(CacheStatus $cach) {
        $this->caches[] = $cach;

        return $this;
    }

    /**
     * Remove cach
     *
     * @param CacheStatus $cach
     */
    public function removeCach(CacheStatus $cach) {
        $this->caches->removeElement($cach);
    }

    /**
     * Get caches
     *
     * @return Collection
     */
    public function getCaches() {
        return $this->caches;
    }

}
