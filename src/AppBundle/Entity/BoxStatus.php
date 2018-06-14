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
 * BoxStatus.
 *
 * @ORM\Table(name="box_status")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BoxStatusRepository")
 */
class BoxStatus extends AbstractEntity {

    /**
     * @var Box
     *
     * @ORM\ManyToOne(targetEntity="Box", inversedBy="status")
     * @ORM\JoinColumn(nullable=false)
     */
    private $box;

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
     * @var array
     *
     * @ORM\Column(name="data", type="array", nullable=false)
     */
    private $data;

    public function __construct() {
        parent::__construct();
        $this->success = false;
        $this->data = array();
    }

    /**
     * Generate a string representation of the status.
     */
    public function __toString() {
        return $this->box . " " . $this->created->format('c');
    }

    /**
     * Set success.
     *
     * @param bool $success
     *
     * @return BoxStatus
     */
    public function setSuccess($success) {
        $this->success = $success;

        return $this;
    }

    /**
     * Get success.
     *
     * @return bool
     */
    public function getSuccess() {
        return $this->success;
    }

    /**
     * Set errors.
     *
     * @param string|array $errors
     *
     * @return BoxStatus
     */
    public function setErrors($errors) {
        if (is_array($errors)) {
            $this->errors = implode("\n", $errors);
        } else {
            $this->errors = $errors;
        }

        return $this;
    }

    /**
     * Get errors.
     *
     * @return string
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Set box.
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
     * Get box.
     *
     * @return Box
     */
    public function getBox() {
        return $this->box;
    }

    /**
     * Set data.
     *
     * @param array $data
     *
     * @return BoxStatus
     */
    public function setData($data) {
            $this->data = $data;

        return $this;
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData() {
        return $this->data;
    }

}
