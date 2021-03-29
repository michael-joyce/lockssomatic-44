<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockss;

use App\Entity\Au;
use App\Entity\Pln;
use App\Repository\AuRepository;
use App\Repository\BoxRepository;
use App\Repository\DepositRepository;
use App\Repository\PlnRepository;
use App\Services\BoxNotifier;
use App\Services\Lockss\LockssService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractLockssCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var AuRepository
     */
    protected $auRepository;

    /**
     * @var BoxRepository
     */
    protected $boxRepository;

    /**
     * @var DepositRepository
     */
    protected $depositRepository;

    /**
     * @var PlnRepository
     */
    protected $plnRepository;

    /**
     * @var BoxNotifier
     */
    protected $notifier;

    /**
     * @var LockssService
     */
    protected $lockssService;

    /**
     * @var ParameterBagInterface
     */
    protected $params;

    public function __construct(LockssService $lockssService, ParameterBagInterface $params, ?string $name = null) {
        parent::__construct($name);
        $this->lockssService = $lockssService;
        $this->params = $params;
    }

    /**
     * @param $auIds
     *
     * @return Au[]
     */
    protected function getAus($auIds) {
        if ($auIds) {
            return $this->auRepository->findBy(['id' => $auIds]);
        }

        return $this->auRepository->findAll();
    }

    protected function getBoxes($plnIds) {
        if ($plnIds) {
            $plns = $this->plnRepository->findBy(['id' => $plnIds]);

            return $this->boxRepository->findBy([
                'pln' => $plns,
                'active' => true,
            ]);
        }

        return $this->boxRepository->findBy(['active' => true]);
    }

    /**
     * @param $plnIds
     *
     * @return Pln[]
     */
    protected function getPlns($plnIds) {
        if ( ! $plnIds) {
            return $this->plnRepository->findAll();
        }

        return $this->plnRepository->findBy(['id' => $plnIds]);
    }

    protected function toArray(stdClass $object) {
        $array = [];

        foreach ($object as $key => $value) {
            if ($value instanceof stdClass) {
                $array[$key] = $this->toArray($value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * @required
     */
    public function setEntityManager(EntityManagerInterface $em) : void {
        $this->em = $em;
    }

    /**
     * @required
     */
    public function setLogger(LoggerInterface $soapLogger) : void {
        $this->logger = $soapLogger;
    }

    /**
     * @required
     */
    public function setAuRepository(AuRepository $repo) : void {
        $this->auRepository = $repo;
    }

    /**
     * @required
     */
    public function setBoxRepository(BoxRepository $repo) : void {
        $this->boxRepository = $repo;
    }

    /**
     * @required
     */
    public function setDepositRepository(DepositRepository $repo) : void {
        $this->depositRepository = $repo;
    }

    /**
     * @required
     */
    public function setPlnRepository(PlnRepository $repo) : void {
        $this->plnRepository = $repo;
    }

    /**
     * @required
     */
    public function setNotifier(BoxNotifier $notifier) : void {
        $this->notifier = $notifier;
    }
}
