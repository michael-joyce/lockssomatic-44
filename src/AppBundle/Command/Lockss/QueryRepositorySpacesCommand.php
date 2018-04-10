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
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function dump;

/**
 * Description of DaemonStatusCommand
 */
class QueryRepositorySpacesCommand extends ContainerAwareCommand {

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
        $this->setName('lockss:au:space');
        $this->setDescription('Report the status of an AU.');
    }

    /**
     * @return Collection|Box[]
     */
    protected function getBoxes() {
        $boxes = $this->em->getRepository(Box::class)->findAll();
        return $boxes;
    }

    public function execute(InputInterface $input, OutputInterface $output) {

        // dev stuff.
        ini_set('soap.wsdl_cache_enabled', '0');
        ini_set('soap.wsdl_cache_ttl', '0');

        $boxes = $this->getBoxes();
        foreach ($boxes as $box) {
            print $box->getUrl() . "\n";
            dump($this->client->queryRepositorySpaces($box));
            foreach($this->client->getErrors() as $e) {
                $output->writeln($e);
            }
        }
    }        

}
