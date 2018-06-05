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
use AppBundle\Services\AuManager;
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
     * @var AuManager
     */
    private $manager;

    /**
     * Build the command.
     *
     * @param EntityManagerInterface $em
     *   Dependency injected doctrine instance.
     * @param LockssClient $client
     *   Dependency injected LOCKSS client.
     */
    public function __construct(EntityManagerInterface $em, LockssClient $client, AuManager $manager) {
        parent::__construct();
        $this->client = $client;
        $this->em = $em;
        $this->manager = $manager;
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
     * @param int $plnId
     *   Filter the deposits to the this PLN ID.
     *
     * @return Generator|Deposit[]
     *   The iterator for the deposits.
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
        $iterator = $qb->getQuery()->iterate();
        foreach($iterator as $row) {
            yield $row[0];
        }
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
        foreach($deposits as $deposit) {
            $output->writeln($deposit->getUuid());
            $boxes = $deposit->getAu()->getPln()->getBoxes();
            $count = 0;
            foreach ($boxes as $box) {
                $output->writeln($box->getIpAddress());
                $hash = $this->client->hash($box, $deposit);
                if($hash === $deposit->getChecksumValue()) {
                    $count++;
                }
                if ($this->client->hasErrors()) {
                    foreach ($this->client->getErrors() as $error) {
                        $output->writeln($error);
                    }
                    $output->writeln('');
                    $this->client->clearErrors();
                }
            }
            $deposit->setAgreement($count / count($boxes));

            if($dryRun) {
                continue;
            }
            $this->em->flush();
        }
    }

}
