<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Command\Lockss;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Pln;
use AppBundle\Services\LockssClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Check the status of deposits in LOCKSS.
 */
class DepositStatusCommand extends ContainerAwareCommand {

    /**
     * Dependency injected doctrine instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * LOCKSS client for SOAP interactions.
     *
     * @var LockssClient
     */
    private $client;

    /**
     * Build the command.
     *
     * @param EntityManagerInterface $em
     *   Dependency injected doctrine instance.
     * @param LockssClient $client
     *   Dependency injected LOCKSS client.
     */
    public function __construct(EntityManagerInterface $em, LockssClient $client) {
        parent::__construct();
        $this->client = $client;
        $this->em = $em;
    }

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('lockss:deposit:status');
        $this->setDescription('Check the status of a deposit.');
        $this->addOption('all', '-a', InputOption::VALUE_NONE, 'Process all deposits.');
        $this->addOption('pln', null, InputOption::VALUE_REQUIRED, 'Optional list of PLNs to check.');
        $this->addOption('limit', '-l', InputOption::VALUE_REQUIRED, 'Limit the number of deposits checked.');
        $this->addOption('dry-run', '-d', InputOption::VALUE_NONE, 'Export only, do not update any internal configs.');
    }

    /**
     * Get a list of deposits to check.
     *
     * By default, only deposits that have not reached agreement are queried.
     *
     * @param bool $all
     *   If true, all deposits will be returned.
     * @param int $limit
     *   Limit the number of deposits returned.
     * @param int $plnId
     *   Filter the deposits to the this PLN ID.
     *
     * @return Deposit[]|Collection
     *   List of deposits to to query.
     */
    protected function getDeposits($all, $limit, $plnId) {
        $repo = $this->em->getRepository(Deposit::class);
        $qb = $repo->createQueryBuilder('d');
        if (!$all) {
            $qb->where('d.agreement <> 1');
            $qb->orWhere('d.agreement is null');
        }
        if ($plnId !== null) {
            $plns = $this->em->getRepository(Pln::class)->findOneBy(array('id' => $plnId));
            $qb->innerJoin('d.contentProvider', 'p', 'WITH', 'p.pln = :pln');
            $qb->setParameter('pln', $plns);
        }
        $qb->orderBy('d.id', 'DESC');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $all = $input->getOption('all');
        $plnId = $input->getOption('pln');
        $dryRun = $input->getOption('dry-run');
        $limit = $input->getOption('limit');

        $deposits = $this->getDeposits($all, $limit, $plnId);
        $output->writeln('Checking deposit status for ' . count($deposits) . ' deposits.');
    }

}
