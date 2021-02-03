<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockss;

use App\Entity\Box;
use App\Entity\Deposit;
use App\Entity\DepositStatus;
use App\Services\Lockss\LockssService;
use App\Utilities\LockssClient;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DepositStatusCommand extends AbstractLockssCommand {
    protected static $defaultName = 'lockss:deposit:status';

    public function __construct(LockssService $lockssService, ParameterBagInterface $params, ?string $name = null) {
        parent::__construct($lockssService, $params, $name);
    }

    protected function configure() : void {
        $this->addOption('pln', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional list of PLNs to check.');
        $this->addOption('uuid', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional list of deposit UUIDs to check.');
        $this->addOption('all', null, InputOption::VALUE_NONE, 'Check or recheck all the deposits.');
        $this->setDescription('Check the status of one or more boxes in a PLN.');
    }

    protected function getStatus(Deposit $deposit, Box $box) {
        $client = LockssClient::create($box);
        $this->lockssService->setClient($client);

        if ( ! $this->lockssService->isUrlCached($deposit)) {
            return '*';
        }

        return $this->lockssService->hash($deposit);
    }

    /**
     * @param $plnIds
     * @param $uuids
     * @param mixed $all
     *
     * @return Deposit[]
     */
    protected function getDeposits($plnIds, $uuids, $all = false) {
        $plns = [];
        if ($plnIds) {
            $plns = $this->getPlns($plnIds);
        }
        $query = $this->depositRepository->checkQuery($plns, $uuids, $all);
        $query->setMaxResults($this->params->get('lockss.deposit.limit'));

        return $query->execute();
    }

    protected function countDeposits($plnIds, $uuids, $all = false) {
        $plns = [];
        if ($plnIds) {
            $plns = $this->getPlns($plnIds);
        }

        return $this->depositRepository->checkQuery($plns, $uuids, $all, true)->getSingleScalarResult();
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $plnIds = $input->getOption('pln');
        $uuids = $input->getOption('uuid');
        $all = $input->getOption('all');

        $count = $this->countDeposits($plnIds, $uuids, $all);
        $output->writeln("Checkking deposits for {$count} deposits.", OutputInterface::VERBOSITY_VERBOSE);

        foreach ($this->getDeposits($plnIds, $uuids, $all) as $deposit) {
            $status = new DepositStatus();
            $status->setDeposit($deposit);

            $boxes = $deposit->getContentProvider()->getPln()->getActiveBoxes();
            $boxCount = count($boxes);
            $matches = 0;
            $result = [];
            $errors = [];
            $hash = null;

            $output->writeln("Checking {$deposit->getUuid()}", OutputInterface::VERBOSITY_VERBOSE);

            foreach ($deposit->getContentProvider()->getPln()->getActiveBoxes() as $box) {
                try {
                    $hash = mb_strtoupper($this->getStatus($deposit, $box));
                } catch (Exception $e) {
                    $hash = '*';
                    $errors[$box->getHostname()] = $e->getMessage();
                    $output->writeln("{$box->getHostname()}: {$e->getMessage()}");
                }
                if ($hash === mb_strtoupper($deposit->getChecksumValue())) {
                    $matches++;
                }
                $result[$box->getHostname() . ':' . $box->getPort()] = $hash;
            }
            if ($matches === $boxCount) {
                $agreement = 1;
            } else {
                $agreement = $matches / count($boxes);
            }
            $output->writeln("Agreement: {$agreement}", OutputInterface::VERBOSITY_VERBOSE);
            $status->setAgreement($agreement);
            $status->setStatus($result);
            $status->setErrors($errors);
            $deposit->setAgreement($agreement);

            $this->em->persist($status);
            $this->em->flush();

            $deposit->setAgreement($agreement);
        }

        return 0;
    }
}
