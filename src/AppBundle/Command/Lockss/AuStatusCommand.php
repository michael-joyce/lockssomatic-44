<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Command\Lockss;

use AppBundle\Entity\Au;
use AppBundle\Services\LockssClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of DaemonStatusCommand.
 */
class AuStatusCommand extends ContainerAwareCommand {

    /**
     * Dependency injected entity manager.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Dependency injected lockss client.
     *
     * @var LockssClient
     */
    private $client;

    /**
     * Construct the command.
     *
     * @param EntityManagerInterface $em
     *   Dependency injected entity manager.
     * @param LockssClient $client
     *   Dependency injected LOCKSS client.
     */
    public function __construct(EntityManagerInterface $em, LockssClient $client) {
        parent::__construct();
        $this->client = $client;
        $this->em = $em;
    }

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('lockss:au:status');
        $this->setDescription('Report the status of an AU.');
    }

    /**
     * Get a list of AUs to check.
     *
     * @return Au[]|Collection
     *   All AUs.
     */
    protected function getAus() {
        $aus = $this->em->getRepository(Au::class)->findAll();
        return $aus;
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     *   Input source.
     * @param OutputInterface $output
     *   Output destination.
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $aus = $this->getAus();
        foreach ($aus as $au) {
            $output->writeln($au->getId());
            foreach ($au->getPln()->getBoxes() as $box) {
                dump($this->client->getAuStatus($box, $au));
                foreach ($this->client->getErrors() as $e) {
                    $output->writeln($e);
                }
            }
        }
    }

}
