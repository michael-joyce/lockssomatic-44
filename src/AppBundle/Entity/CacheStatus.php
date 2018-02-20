<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * CacheStatus.
 *
 * @ORM\Table(name="cache_status")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CacheStatusRepository")
 */
class CacheStatus extends AbstractEntity {

    /**
     * @var BoxStatus
     *
     * @ORM\ManyToOne(targetEntity="BoxStatus", inversedBy="caches")
     * @ORM\JoinColumn(nullable=false)
     */
    private $boxStatus;

    /**
     * The response from LOCKSS for one cache.
     *
     * @var array
     *
     * @ORM\Column(name="response", type="array", nullable=false)
     */
    private $response;

    /**
     *
     */
    public function __toString() {
        return (string) $this->boxStatus;
    }

    /**
     * Set response.
     *
     * @param array $response
     *
     * @return CacheStatus
     */
    public function setResponse($response) {
        $this->response = $response;

        return $this;
    }

    /**
     * Get response.
     *
     * @return array
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Set boxStatus.
     *
     * @param BoxStatus $boxStatus
     *
     * @return CacheStatus
     */
    public function setBoxStatus(BoxStatus $boxStatus = null) {
        $this->boxStatus = $boxStatus;

        return $this;
    }

    /**
     * Get boxStatus.
     *
     * @return BoxStatus
     */
    public function getBoxStatus() {
        return $this->boxStatus;
    }

}
