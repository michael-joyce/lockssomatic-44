<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ContentOwner.
 *
 * @ORM\Table(name="content_owner")
 * @ORM\Entity(repositoryClass="App\Repository\ContentOwnerRepository")
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
     * @Assert\Email(mode="html5")
     */
    private $emailAddress;

    /**
     * List of content providers for this owner.
     *
     * @var Collection|ContentProvider[]
     *
     * @ORM\OneToMany(targetEntity="ContentProvider", mappedBy="contentOwner")
     */
    private $contentProviders;

    public function __toString() : string {
        return $this->name;
    }

    /**
     * Set name.
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
     * Get name.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set emailAddress.
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
     * Get emailAddress.
     *
     * @return string
     */
    public function getEmailAddress() {
        return $this->emailAddress;
    }

    /**
     * Add contentProvider.
     *
     * @return ContentOwner
     */
    public function addContentProvider(ContentProvider $contentProvider) {
        $this->contentProviders[] = $contentProvider;

        return $this;
    }

    /**
     * Remove contentProvider.
     */
    public function removeContentProvider(ContentProvider $contentProvider) : void {
        $this->contentProviders->removeElement($contentProvider);
    }

    /**
     * Get contentProviders.
     *
     * @return Collection
     */
    public function getContentProviders() {
        return $this->contentProviders;
    }
}
