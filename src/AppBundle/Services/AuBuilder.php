<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\Content;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Description of AuBuilder
 */
class AuBuilder {
    
    /**
     * @var AuPropertyGenerator
     */
    private $propertyGenerator;
    
    /**
     * @var AuIdGenerator
     */
    private $idGenerator;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param EntityManagerInterface $em
     * @param AuPropertyGenerator $propertyGenerator
     * @param AuIdGenerator $idGenerator
     */
    public function __construct(EntityManagerInterface $em, AuPropertyGenerator $propertyGenerator, AuIdGenerator $idGenerator) {
        $this->em = $em;
        $this->propertyGenerator = $propertyGenerator;
        $this->idGenerator = $idGenerator;
    }
    
    /**
     * @param Content $content
     * @return Au
     */
    public function fromContent(Content $content) {
        $au = new Au();
        $au->addContent($content);
        $provider = $content->getDeposit()->getContentProvider();
        $au->setContentProvider($provider);
        $au->setPln($provider->getPln());
        $au->setPlugin($provider->getPlugin());
        $this->em->persist($au);
        $this->propertyGenerator->generateProperties($au);
        //$au->setAuid($this->idGenerator->fromAu($au));
        return $au;
    }
    
}
