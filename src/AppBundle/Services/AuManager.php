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
 * Description of AuManager.
 */
class AuManager {

    /**
     * Property generator service.
     *
     * @var AuPropertyGenerator
     */
    private $propertyGenerator;

    /**
     * AUID generator service.
     *
     * @var AuIdGenerator
     */
    private $idGenerator;

    /**
     * Database mapper.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Build the builder.
     *
     * @param EntityManagerInterface $em
     *   Dependency injected entity manager.
     * @param AuPropertyGenerator $propertyGenerator
     *   Dependency injected property generator.
     * @param AuIdGenerator $idGenerator
     *   Dependency injected ID generator.
     */
    public function __construct(EntityManagerInterface $em, AuPropertyGenerator $propertyGenerator, AuIdGenerator $idGenerator) {
        $this->em = $em;
        $this->propertyGenerator = $propertyGenerator;
        $this->idGenerator = $idGenerator;
    }

    /**
     * Build the AU from content.
     *
     * Persists the new AU, but does not flush it to the database.
     *
     * @param Content $content
     *   Initial content for the AU.
     *
     * @return Au
     *   The new AU.
     */
    public function findByContent(Content $content) {
        $au = $this->em->getRepository(Au::class)->findOpenAu($content);
        if (!$au) {
            $au = new Au();
            $provider = $content->getDeposit()->getContentProvider();
            $au->setContentProvider($provider);
            $au->setPln($provider->getPln());
            $au->setPlugin($provider->getPlugin());
            $this->em->persist($au);
        }
        $au->addContent($content);
        $content->setAu($au);
        return $au;
    }

}
