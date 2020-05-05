<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Command\Lockss;

use AppBundle\Entity\Au;
use AppBundle\Entity\AuStatus;
use AppBundle\Entity\Pln;
use AppBundle\Services\LockssClient;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of DaemonStatusCommand.
 */
class AuStatusCommand extends ContainerAwareCommand {
    /**
     * Dependency injected entity manager.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Dependency injected lockss client.
     *
     * @var LockssClient
     */
    private $client;

    /**
     * Construct the command.
     */
    public function __construct(EntityManagerInterface $em, LockssClient $client) {
        parent::__construct();
        $this->client = $client;
        $this->em = $em;
    }

    /**
     * Configure the command.
     */
    protected function configure() : void {
        $this->setName('lockss:au:status');
        $this->addOption('pln', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional list of PLNs to check.');
        $this->addOption('dry-run', '-d', InputOption::VALUE_NONE, 'Export only, do not update any internal configs.');
        $this->setDescription('Report the status of an AU.');
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

    protected function queryAu(Au $au, $boxes) {
        $status = [];
        $errors = [];
        foreach ($boxes as $box) {
            $status[$box . ':' . $box->getWebServicePort()] = $this->client->getAuStatus($box, $au);
            if ($this->client->hasErrors()) {
                $errors = array_merge($errors, $this->client->getErrors());
                $this->client->clearErrors();
            }
        }
        $auStatus = new AuStatus();
        $auStatus->setAu($au);
        $auStatus->setStatus($status);
        $auStatus->setErrors($errors);
        $this->em->persist($auStatus);

        return $auStatus;
    }

    protected function queryPln(Pln $pln, $dryRun) : void {
        $boxes = $pln->getActiveBoxes();

        foreach ($pln->getAus() as $au) {
            $this->queryAu($au, $boxes);
            if ( ! $dryRun) {
                $this->em->flush();
            }
        }
    }

    /**
     * Execute the command.
     */
    public function execute(InputInterface $input, OutputInterface $output) : void {
        $plnIds = $input->getOption('pln');
        $dryRun = $input->getOption('dry-run');

        $plns = $this->getPlns($plnIds);
        foreach ($plns as $pln) {
            $this->queryPln($pln, $dryRun);
        }
    }
}
