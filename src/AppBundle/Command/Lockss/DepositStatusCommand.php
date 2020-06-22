<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Command\Lockss;

use AppBundle\Entity\Au;
use AppBundle\Entity\Box;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\DepositStatus;
use AppBundle\Entity\Pln;
use AppBundle\Services\AuManager;
use AppBundle\Services\LockssClient;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
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
     * Au manager service.
     *
     * @var AuManager
     */
    private $manager;

    /**
     * Build the command.
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
    protected function configure() : void {
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
     * @return Collection|Pln[]
     */
    protected function getPlns(array $plnIds) {
        $repo = $this->em->getRepository(Pln::class);
        if (count($plnIds) > 0) {
            return $repo->findBy(['id' => $plnIds]);
        }

        return $repo->findAll();
    }

    /**
     * Count the deposits which will be checked.
     *
     * @param $all
     * @param $plnId
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @return int
     */
    protected function countDeposits($all, $plnId) {
        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(d) as ct')->from(Deposit::class, 'd');

        if ( ! $all) {
            $qb->where('d.agreement <> 1 OR d.agreement IS NULL');
            $qb->andWhere('d.checked IS NULL OR DATE_DIFF(:now, d.checked) > 1');
            $qb->setParameter('now', new DateTime());
        }
        if (null !== $plnId) {
            $plns = $this->em->getRepository('LOCKSSOMaticCrudBundle:Pln')->findOneBy(['id' => $plnId]);
            $qb->innerJoin('d.contentProvider', 'p', 'WITH', 'p.pln = :pln');
            $qb->setParameter('pln', $plns);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get a list of deposits to check.
     *
     * @param bool $all
     * @param int $limit
     * @param int $plnId
     *
     * @throws Exception
     *
     * @return Deposit[]|Generator
     */
    protected function getDeposits($all, $limit, $plnId) {
        $repo = $this->em->getRepository(Deposit::class);
        $qb = $repo->createQueryBuilder('d');
        if ( ! $all) {
            $qb->where('d.agreement <> 1 OR d.agreement IS NULL');
            $qb->andWhere('d.checked IS NULL OR DATE_DIFF(:now, d.checked) > 1');
            $qb->setParameter('now', new DateTime());
        }
        if (null !== $plnId) {
            $plns = $this->em->getRepository(Pln::class)->findOneBy(['id' => $plnId]);
            $qb->innerJoin('d.contentProvider', 'p', 'WITH', 'p.pln = :pln');
            $qb->setParameter('pln', $plns);
        }
        $qb->orderBy('d.checked');
        $qb->addOrderBy('d.id', 'DESC');
        if ($limit) {
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
     * @param Box[]|Collection $boxes
     *
     * @return DepositStatus
     */
    protected function queryDeposit(Deposit $deposit, $boxes) {
        $boxCount = count($boxes);
        $agree = 0;
        $status = [];
        $errors = [];

        foreach ($boxes as $box) {
            $checksum = $this->client->hash($box, $deposit);
            if ($this->client->hasErrors()) {
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

    /**
     * Query the deposits in a PLN.
     *
     * If $all is true, all deposits will be queried regardless of status. If
     * $dryRun is true the results will not be flushed to the database. If $limit
     * is not zero, only the first $limit deposits will be checked.
     *
     * @param bool $all
     * @param bool $dryRun
     * @param int $limit
     */
    protected function queryPln(Pln $pln, $all, $dryRun, $limit) : void {
        $boxes = $pln->getActiveBoxes();

        $deposits = $this->getDeposits($all, $limit);
        foreach ($deposits as $deposit) {
            $this->queryDeposit($deposit, $boxes);
            if ( ! $dryRun) {
                $this->em->flush();
            }
            //$this->em->detach($deposit);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output) : void {
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
