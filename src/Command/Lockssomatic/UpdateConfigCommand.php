<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockssomatic;

use App\Entity\Pln;
use App\Services\ConfigUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update the PLN configuration.
 */
class UpdateConfigCommand extends ContainerAwareCommand {
    /**
     * Doctrine instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Dependency injected updater service.
     *
     * @var ConfigUpdater
     */
    private $updater;

    /**
     * Build the updater command.
     */
    public function __construct(EntityManagerInterface $em, ConfigUpdater $updater) {
        $this->em = $em;
        $this->updater = $updater;

        parent::__construct();
    }

    /**
     * Determine which PLNs should be updated.
     *
     * @param array $plnIds
     *
     * @return Pln[]
     */
    private function getPlns(array $plnIds = null) {
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
        $this->setName('lom:update:config');
        $this->setDescription('Update the configuration properties of one or more PLN.');
        $this->addArgument('pln', InputArgument::IS_ARRAY, 'Optional list of database PLN IDs to update.');
    }

    /**
     * Execute the command.
     */
    protected function execute(InputInterface $input, OutputInterface $output) : void {
        $ids = $input->getArgument('pln');
        foreach ($this->getPlns($ids) as $pln) {
            $output->writeln("updating {$pln->getName()}");
            $this->updater->update($pln);
            $this->em->flush();
        }
    }
}
