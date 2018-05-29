<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Command\Lockss;

use AppBundle\Entity\Box;
use AppBundle\Services\LockssClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function dump;

/**
 * List the repositories in the boxes in a network.
 */
class QueryRepositoriesCommand extends ContainerAwareCommand {

    /**
     * Doctrine instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * LOCKSS client service.
     *
     * @var LockssClient
     */
    private $client;

    /**
     * Build the command.
     *
     * @param EntityManagerInterface $em
     *   Dependency injected doctrine instance.
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
        $this->setName('lockss:au:query');
        $this->setDescription('Report the status of an AU.');
    }

    /**
     * Get the boxes to check.
     *
     * @return \Doctrine\Common\Collections\Collection|Box[]
     */
    protected function getBoxes() {
        $boxes = $this->em->getRepository(Box::class)->findAll();
        return $boxes;
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $boxes = $this->getBoxes();
        foreach ($boxes as $box) {
            print $box->getUrl() . "\n";
            dump($this->client->queryRepositories($box));
            foreach ($this->client->getErrors() as $e) {
                $output->writeln($e);
            }
        }
    }

}
