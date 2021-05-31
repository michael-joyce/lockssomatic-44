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
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuListCommand extends AbstractLockssCommand {
    protected static $defaultName = 'lockss:aus:list';

    public function __construct(LockssService $lockssService, ParameterBagInterface $params, ?string $name = null) {
        parent::__construct($lockssService, $params, $name);
    }

    protected function configure() : void {
        $this->addOption('pln', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional list of PLNs to check.');
        $this->setDescription('List the AUs in the boxes in a network.');
    }

    protected function listAus(Box $box) {
        $result = [];

        try {
            $result = $this->lockssService->listAus($box);
        } catch (Exception $e) {
            $this->logger->error("{$box->getIpAddress()} - {$e->getMessage()}");
        }

        return $result;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $plnIds = $input->getOption('pln');
        $boxes = $this->getBoxes($plnIds);
        $output->writeln('listing AUs in ' . count($boxes) . ' boxes.');

        foreach ($this->getBoxes($plnIds) as $box) {
            $output->writeln($box);
            $output->writeln('');

            foreach ($this->listAus($box) as $result) {
                $output->writeln('  ' . $result->name);
                $output->writeln($result->id);
                $output->writeln('');
            }
        }

        return 0;
    }
}
