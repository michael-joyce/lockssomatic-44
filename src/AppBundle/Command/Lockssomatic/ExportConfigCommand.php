<?php

namespace AppBundle\Command\Lockssomatic;

use AppBundle\Entity\Pln;
use AppBundle\Services\ConfigExporter;
use AppBundle\Services\ConfigUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * LomExportConfigCommand command.
 */
class ExportConfigCommand extends ContainerAwareCommand {

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
     *
     * @param EntityManagerInterface $em
     *   Dependency injected doctrine instance.
     * @param ConfigExporter $exporter
     *   Dependency injected configuration exporter service.
     * @param ConfigUpdater $updater
     *   Dependency injected configuration updater service.
     */
    public function __construct(EntityManagerInterface $em, ConfigExporter $exporter, ConfigUpdater $updater) {
        $this->em = $em;
        $this->exporter = $exporter;
        $this->updater = $updater;
        parent::__construct();
    }

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('lom:export:config');
        $this->addOption('update', null, InputOption::VALUE_NONE, 'Update the configs before exporting.');
        $this->setDescription('Export the configuration for one or more PLNs.');
        $this->addArgument('pln', InputArgument::IS_ARRAY, 'Optional list of database PLN IDs to update.');
    }

    /**
     * Get the PLNs for export.
     *
     * @param array $plnIds
     *   If this array is empty all PLNs are returned.
     *
     * @return Pln[]
     *   List of PLNs to update.
     */
    private function getPlns(array $plnIds = null) {
        $repo = $this->em->getRepository(Pln::class);
        if ($plnIds === null || count($plnIds) === 0) {
            return $repo->findAll();
        }
        return $repo->findById($plnIds);
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     *   Command input, as defined in the configure() method.
     * @param OutputInterface $output
     *   Output destination.
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $plnIds = $input->getArgument('pln');
        foreach ($this->getPlns($plnIds) as $pln) {
            if ($input->getOption('update')) {
                $output->writeln("update {$pln->getName()}");
                $this->updater->update($pln);
                $this->em->flush();
            }
            $output->writeln("exporting {$pln->getName()}");
            $this->exporter->export($pln);
        }
    }

}
