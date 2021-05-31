<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockss;

use App\Entity\Au;
use App\Entity\Box;
use App\Services\Lockss\LockssService;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ListAuContentCommand extends AbstractLockssCommand {
    protected static $defaultName = 'lockss:au:content';

    public function __construct(LockssService $lockssService, ParameterBagInterface $params, ?string $name = null) {
        parent::__construct($lockssService, $params, $name);
    }

    protected function configure() : void {
        $this->addOption('auId', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional list of AU database IDs to check.');
        $this->setDescription('List the AUs in the boxes in a network.');
    }

    protected function listAuContent(Au $au, Box $box) {
        $result = [];

        try {
            $result = $this->lockssService->listAuUrls($box, $au);
        } catch (Exception $e) {
            $this->logger->error("{$box->getIpAddress()} - {$e->getMessage()}");
        }

        return $result;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $auIds = $input->getOption('auId');
        $aus = $this->getAus($auIds);

        foreach ($this->getAus($auIds) as $au) {
            $output->writeln($au->getSimpleAuProperty('title'));
            $boxes = $au->getPln()->getActiveBoxes();

            foreach ($boxes as $box) {
                $output->writeln('  ' . $box->getHostname());
                $this->listAuContent($au, $box);

                foreach ($this->listAuContent($au, $box) as $result) {
                    $output->writeln('    ' . $result);
                }
            }
        }

        return 0;
    }
}
