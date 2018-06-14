<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Command\Lockss;

use AppBundle\Entity\Au;
use AppBundle\Entity\Box;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\DepositStatus;
use AppBundle\Entity\Pln;
use AppBundle\Services\AuManager;
use AppBundle\Services\LockssClient;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
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
     * @param LockssClient $client
     * @param AuManager $manager
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
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit the number of deposits checked.');
        $this->addOption('pln', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional list of PLNs to check.');
        $this->addOption('dry-run', '-d', InputOption::VALUE_NONE, 'Export only, do not update any internal configs.');
    }

    /**
     * Get a list of PLNs to query.
     *
     * @param array $plnIds
     *
     * @return Collection|Pln[]
     */
    protected function getPlns(array $plnIds) {
        $repo = $this->em->getRepository(Pln::class);
        if (count($plnIds) > 0) {
            return $repo->findBy(array('id' => $plnIds));
        }
        return $repo->findAll();
    }

    /**
     * Get a list of deposits to check.
     *
     * @param Au $au
     * @param boolean $all
     *
     * @return Generator|Deposit[]
     */
    protected function getDeposits(Au $au, $all, $limit = null) {
        $repo = $this->em->getRepository(Deposit::class);
        $qb = $repo->createQueryBuilder('d');
        $qb->andWhere('d.au = :au');
        $qb->setParameter('au', $au);
        if (!$all) {
            $qb->andWhere('(d.agreement is null OR d.agreement <> 1)');
        }
        if($limit) {
            $qb->setMaxResults($limit);
        }
        $iterator = $qb->getQuery()->iterate();
        foreach ($iterator as $row) {
            yield $row[0];
        }
    }

    /**
     * Query one deposit across all the boxes in the deposit's network.
     *
     * @param Deposit $deposit
     * @param Collection|Box[] $boxes
     *
     * @return DepositStatus
     */
    protected function queryDeposit(Deposit $deposit, $boxes) {
        $boxCount = count($boxes);
        $agree = 0;
        $status = [];
        $errors = [];

        foreach ($boxes as $box) {
            if (!$box->getActive()) {
                continue;
            }
            $checksum = $this->client->hash($box, $deposit);
            if($this->client->hasErrors()) {
                $errors = array_merge($errors, $this->client->getErrors());
                $this->client->clearErrors();
            }
            $status[$box . ':' . $box->getWebServicePort()] = $checksum;
            if ($checksum === $deposit->getChecksumValue()) {
                $agree++;
            }
        }
        $depositStatus = new DepositStatus();
        $depositStatus->setDeposit($deposit);
        $depositStatus->setAgreement($agree / $boxCount);
        $depositStatus->setStatus($status);
        $depositStatus->setErrors($errors);
        $deposit->setAgreement($agree / $boxCount);
        $this->em->persist($depositStatus);
        return $depositStatus;
    }

    protected function queryPln(Pln $pln, $all, $dryRun, $limit) {
        $boxes = $pln->getBoxes();

        foreach ($pln->getAus() as $au) {
            $deposits = $this->getDeposits($au, $all, $limit);
            foreach ($deposits as $deposit) {
                $this->queryDeposit($deposit, $boxes);
                if( ! $dryRun) {
                    $this->em->flush();
                }
                //$this->em->detach($deposit);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $all = $input->getOption('all');
        $plnIds = $input->getOption('pln');
        $limit = $input->getOption('limit');
        $dryRun = $input->getOption('dry-run');

        $plns = $this->getPlns($plnIds);
        foreach ($plns as $pln) {
            $this->queryPln($pln, $all, $dryRun, $limit);
        }
    }

}
