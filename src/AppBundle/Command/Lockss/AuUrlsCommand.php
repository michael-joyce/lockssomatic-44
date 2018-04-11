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
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of DaemonStatusCommand
 */
class AuUrlsCommand extends ContainerAwareCommand {

    /**
     * @var EntityManagerInterface
     */
    private $em;
    
    /**
     * @var LockssClient
     */
    private $client;

    public function __construct(EntityManagerInterface $em, LockssClient $client) {
        parent::__construct();
        $this->client = $client;
        $this->em = $em;
    }

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('lockss:au:urls');
        $this->setDescription('Report the urls preserved in an AU.');
    }

    /**
     * @return Au[]|Collection
     */
    protected function getAus() {
        $aus = $this->em->getRepository(Au::class)->findAll();
        return $aus;
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $aus = $this->getAus();
        foreach($aus as $au) {
            $output->writeln($au->getId());
            foreach($au->getPln()->getBoxes() as $box) {
                dump($this->client->getAuUrls($box, $au));
                foreach($this->client->getErrors() as $e) {
                    $output->writeln($e);
                }
            }
        }
    }        

}
