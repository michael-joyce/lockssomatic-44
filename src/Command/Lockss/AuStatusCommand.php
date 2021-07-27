<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockss;

use App\Entity\Au;
use App\Entity\AuStatus;
use App\Entity\Box;
use App\Services\Lockss\LockssService;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuStatusCommand extends AbstractLockssCommand {
    protected static $defaultName = 'lockss:au:status';

    public function __construct(LockssService $lockssService, ParameterBagInterface $params, ?string $name = null) {
        parent::__construct($lockssService, $params, $name);
    }

    protected function configure() : void {
        $this->addOption('pln', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional list of PLNs to check.');
        $this->addOption('dry-run', '-d', InputOption::VALUE_NONE, 'Do not update box status, just report results to console.');
        $this->setDescription('Check the status of one or more AUs in a PLN.');
    }

    protected function getStatus(Au $au, Box $box) {
        return $this->lockssService->auStatus($box, $au);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $dryRun = $input->getOption('dry-run');
        $plnIds = $input->getOption('pln');

        foreach ($this->getPlns($plnIds) as $pln) {
            $boxes = $pln->getActiveBoxes();

            foreach ($pln->getAus() as $au) {
                $auStatus = new AuStatus();
                $auStatus->setAu($au);

                foreach ($boxes as $box) {
                    try {
                        $status = $this->getStatus($au, $box);
                        $auStatus->addStatus($box, $this->toArray($status));
                    } catch (Exception $e) {
                        $output->writeln("AU status error {$e->getMessage()}", );
                        $auStatus->addError($box, $e->getMessage());
                    }
                }
                if( ! $dryRun) {
                    $this->em->persist($auStatus);
                    $this->em->flush();
                }
            }
        }

        return 0;
    }
}
