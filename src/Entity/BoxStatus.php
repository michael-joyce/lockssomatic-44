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
 * BoxStatus.
 *
 * @ORM\Table(name="box_status")
 * @ORM\Entity(repositoryClass="App\Repository\BoxStatusRepository")
 */
class BoxStatus extends AbstractEntity
{
    /**
     * Box that owns the status.
     *
     * @var Box
     *
     * @ORM\ManyToOne(targetEntity="Box", inversedBy="status")
     * @ORM\JoinColumn(nullable=false)
     */
    private $box;

    /**
     * Was the status check successful?
     *
     * @var bool
     *
     * @ORM\Column(name="success", type="boolean")
     */
    private $success;

    /**
     * Errors during the status check.
     *
     * @var string
     *
     * @ORM\Column(name="errors", type="text", nullable=true)
     */
    private $errors;

    /**
     * Status data.
     *
     * @var array
     *
     * @ORM\Column(name="data", type="array", nullable=false)
     */
    private $data;

    public function __construct() {
        parent::__construct();
        $this->success = false;
        $this->data = [];
    }

    /**
     * Generate a string representation of the status.
     */
    public function __toString() : string {
        return $this->box . ' ' . $this->created->format('c');
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
     * @param array|string $errors
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
    public function setBox(?Box $box = null) {
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
     * @return BoxStatus
     */
    public function setData(array $data) {
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
