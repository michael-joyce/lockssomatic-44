<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockssomatic;

use App\Entity\Au;
use App\Services\AuManager;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * LomValidateAuCommand command.
 */
class ListAuContentCommand extends Command {
    /**
     * AU Manager.
     *
     * @var AuManager
     */
    private $manager;

    /**
     * Doctrine instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Construct the command.
     */
    public function __construct(EntityManagerInterface $em, AuManager $manager) {
        parent::__construct(null);
        $this->manager = $manager;
        $this->em = $em;
    }

    /**
     * Configure the command.
     */
    protected function configure() : void {
        $this->setName('lom:list:au');
        $this->setDescription('List the content in an AU.');
        $this->addArgument('ids', InputArgument::IS_ARRAY, 'List of AU database ids to check');
    }

    /**
     * Fetch a list of AUs to query from the database.
     *
     * @return Au[]|Collection
     */
    protected function getAus(array $ids) {
        if (0 === count($ids)) {
            return $this->em->getRepository(Au::class)->findAll();
        }

        return $this->em->getRepository(Au::class)->findBy([
            'id' => $ids,
        ]);
    }

    /**
     * Execute the command.
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int {
        foreach ($this->getAus($input->getArgument('ids')) as $au) {
            $iterator = $this->em->getRepository(Au::class)->iterateDeposits($au);
            while (($deposit = $iterator->current())) {
                $output->writeln($deposit->getUrl());
                $iterator->next();
            }
        }
        return 0;
    }
}
