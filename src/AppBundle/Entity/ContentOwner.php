<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ContentOwner
 *
 * @ORM\Table(name="content_owner")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContentOwnerRepository")
 */
class ContentOwner extends AbstractEntity {

    /**
     * Name of the content owner.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * Email address for the content owner.
     *
     * @var string
     *
     * @ORM\Column(name="email_address", type="text", nullable=true)
     * @Assert\Email(strict=true)
     */
    private $emailAddress;

    /**
     * @ORM\OneToMany(targetEntity="ContentProvider", mappedBy="contentOwner")
     *
     * @var Collection|ContentProvider[]
     */
    private $contentProviders;

    public function __toString() {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ContentOwner
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set emailAddress
     *
     * @param string $emailAddress
     *
     * @return ContentOwner
     */
    public function setEmailAddress($emailAddress) {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Get emailAddress
     *
     * @return string
     */
    public function getEmailAddress() {
        return $this->emailAddress;
    }

    /**
     * Add contentProvider
     *
     * @param ContentProvider $contentProvider
     *
     * @return ContentOwner
     */
    public function addContentProvider(ContentProvider $contentProvider) {
        $this->contentProviders[] = $contentProvider;

        return $this;
    }

    /**
     * Remove contentProvider
     *
     * @param ContentProvider $contentProvider
     */
    public function removeContentProvider(ContentProvider $contentProvider) {
        $this->contentProviders->removeElement($contentProvider);
    }

    /**
     * Get contentProviders
     *
     * @return Collection
     */
    public function getContentProviders() {
        return $this->contentProviders;
    }

}
