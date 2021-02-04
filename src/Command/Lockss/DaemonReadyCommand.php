<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockss;

use App\Entity\Box;
use App\Services\Lockss\LockssService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DaemonReadyCommand extends AbstractLockssCommand {
    protected static $defaultName = 'lockss:daemon:ready';

    public function __construct(LockssService $lockssService, ParameterBagInterface $params, ?string $name = null) {
        parent::__construct($lockssService, $params, $name);
    }

    protected function configure() : void {
        $this->addOption('pln', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional list of PLNs to check.');
        $this->setDescription('Check the status of one or more boxes in a PLN.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $plnIds = $input->getOption('pln');
        $boxes = $this->getBoxes($plnIds);

        $output->writeln('Checking status of ' . count($boxes) . ' boxes.');

        foreach ($this->getBoxes($plnIds) as $box) {
            $output->write("Checking ready status on {$box->getHostname()}. ");
            $status = $this->lockssService->isDaemonReady($box);
            if ($status) {
                $output->writeln('Daemon reports ready.');
            } else {
                $output->writeln('Daemon reports not ready.');
            }
        }

        return 0;
    }
}
