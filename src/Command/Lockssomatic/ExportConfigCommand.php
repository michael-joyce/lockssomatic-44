<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockssomatic;

use App\Entity\Pln;
use App\Services\ConfigExporter;
use App\Services\ConfigUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * LomExportConfigCommand command.
 */
class ExportConfigCommand extends Command {
    /**
     * Exporter service instance.
     *
     * @var ConfigExporter
     */
    private $exporter;

    /**
     * Configuration updater instance.
     *
     * @var ConfigUpdater
     */
    private $updater;

    /**
     * Doctrine entity manager instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Construct the command.
     */
    public function __construct(EntityManagerInterface $em, ConfigExporter $exporter, ConfigUpdater $updater) {
        $this->em = $em;
        $this->exporter = $exporter;
        $this->updater = $updater;
        parent::__construct();
    }

    /**
     * Get the PLNs for export.
     *
     * @param array $plnIds
     *
     * @return Pln[]
     */
    private function getPlns(?array $plnIds = null) {
        $repo = $this->em->getRepository(Pln::class);
        if (null === $plnIds || 0 === count($plnIds)) {
            return $repo->findAll();
        }

        return $repo->findById($plnIds);
    }

    /**
     * Configure the command.
     */
    protected function configure() : void {
        $this->setName('lom:export:config');
        $this->addOption('update', null, InputOption::VALUE_NONE, 'Update the configs before exporting.');
        $this->setDescription('Export the configuration for one or more PLNs.');
        $this->addArgument('pln', InputArgument::IS_ARRAY, 'Optional list of database PLN IDs to update.');
    }

    /**
     * Execute the command.
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $plnIds = $input->getArgument('pln');

        foreach ($this->getPlns($plnIds) as $pln) {
            if ($input->getOption('update')) {
                $output->writeln("update {$pln->getName()}", OutputInterface::VERBOSITY_VERBOSE);
                $this->updater->update($pln);
                $this->em->flush();
            }
            $output->writeln("exporting {$pln->getName()}", OutputInterface::VERBOSITY_VERBOSE);
            $this->exporter->export($pln);
        }

        return 0;
    }
}
