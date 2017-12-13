<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * Keystore
 *
 * @ORM\Table(name="keystore")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\KeystoreRepository")
 */
class Keystore extends AbstractEntity {

    /**
     * The PLN this keystore is a part of.
     *
     * @var Pln
     *
     * @ORM\OneToOne(targetEntity="Pln", mappedBy="keystore")
     */
    private $pln;

    /**
     * Path, in the local file system, to the keystore file.
     *
     * @var string
     * @ORM\Column(name="path", type="string", length=255)
     */
    private $path;

    /**
     * Original filename, as uploaded in the web form.
     *
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255)
     */
    private $filename;

    public function __toString() {
        return $this->filename;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return Keystore
     */
    public function setPath($path) {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Set filename
     *
     * @param string $filename
     *
     * @return Keystore
     */
    public function setFilename($filename) {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename() {
        return $this->filename;
    }

    /**
     * Set pln
     *
     * @param Pln $pln
     *
     * @return Keystore
     */
    public function setPln(Pln $pln = null) {
        $this->pln = $pln;

        return $this;
    }

    /**
     * Get pln
     *
     * @return Pln
     */
    public function getPln() {
        return $this->pln;
    }

}
