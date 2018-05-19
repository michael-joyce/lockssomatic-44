<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\Deposit;
use AppBundle\Repository\AuRepository;
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
     * @var AuRepository
     */
    private $auRepository;

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
        $this->auRepository = $em->getRepository(Au::class);
    }

    public function setAuRepository(AuRepository $repo) {
        $this->auRepository = $repo;
    }

    /**
     * Calculate the size of an AU.
     *
     * @param Au $au
     * @return int
     */
    public function auSize(Au $au) {
        return $this->auRepository->getAuSize($au);
    }

    public function buildAu(Deposit $deposit, $auid) {
        $provider = $deposit->getContentProvider();
        $au = new Au();
        $au->setContentProvider($provider);
        $au->setPln($provider->getPln());
        $au->setAuid($auid);
        $au->setPlugin($provider->getPlugin());
        $this->em->persist($au);
        return $au;
    }

    /**
     * Build the AU from content.
     *
     * Persists the new AU, but does not flush it to the database.
     *
     * @param Deposit $deposit
     *   Initial content for the AU.
     *
     * @return Au
     *   The new AU.
     */
    public function findOpenAu(Deposit $deposit) {
        $provider = $deposit->getContentProvider();
        $auid = $this->idGenerator->fromDeposit($deposit, false);
        $au = $this->auRepository->findOpenAu($auid);
        if ($au && $this->auSize($au) + $deposit->getSize() > $provider->getMaxAuSize()) {
            $au->setOpen(false);
            $this->auRepository->flush($au);
            $au = null;
        }
        if (!$au) {
            $au = $this->buildAu($deposit, $auid);
        }
        $au->addDeposit($deposit);
        $deposit->setAu($au);
        return $au;
    }

}
