<?php

namespace AppBundle\Command;

use AppBundle\Entity\Pln;
use AppBundle\Services\ConfigUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * LomExportConfigCommand command.
 */
class UpdateConfigCommand extends ContainerAwareCommand
{
    private $em;
    
    private $updater;
    
    public function __construct(EntityManagerInterface $em, ConfigUpdater $updater) {
        $this->em = $em;
        $this->updater = $updater;
        
        parent::__construct();
    }
    
    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this->setName('lom:update:config');
        $this->setDescription('Update the configuration properties of one or more PLN.');
        $this->addArgument('pln', InputArgument::IS_ARRAY, 'Optional list of database PLN IDs to update.');
    }

    /**
     * @param array $plnIds
     * @return Pln[]
     */
    private function getPlns($plnIds = null) {
        $repo = $this->em->getRepository(Pln::class);
        if($plnIds === null || count($plnIds) === 0) {
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ids = $input->getArgument('pln');
        foreach($this->getPlns($ids) as $pln) {
            $output->writeln("updating {$pln->getName()}");
            $this->updater->update($pln);
            $this->em->flush();
        }
    }

}
