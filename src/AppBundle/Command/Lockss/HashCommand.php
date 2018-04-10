<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Command\Lockss;

use AppBundle\Entity\Content;
use AppBundle\Services\LockssClient;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of DaemonStatusCommand
 */
class HashCommand extends ContainerAwareCommand {

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
        $this->setName('lockss:content:hash');
        $this->setDescription('Report the status of an AU.');
    }

    /**
     * @return Content[]|Collection
     */
    protected function getContents() {
        $contents = $this->em->getRepository(Content::class)->findAll();
        return $contents;
    }

    public function execute(InputInterface $input, OutputInterface $output) {

        // dev stuff.
        ini_set('soap.wsdl_cache_enabled', '0');
        ini_set('soap.wsdl_cache_ttl', '0');

        $contents = $this->getContents();
        foreach($contents as $content) {
            $output->writeln($content->getUrl());
            foreach($content->getAu()->getPln()->getBoxes() as $box) {
                $output->writeln($box->getIpAddress());
                dump($this->client->hash($box, $content));
                if($this->client->hasErrors()) {
                    foreach($this->client->getErrors() as $error) {
                        $output->writeln($error);
                    }
                    $output->writeln('');
                    $this->client->clearErrors();
                }
            }
        }
    }        

}
