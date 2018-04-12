<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Command\Lockss;

use AppBundle\Entity\Au;
use AppBundle\Entity\Box;
use AppBundle\Services\LockssClient;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Description of AbstractLockssCommand
 */
abstract class AbstractLockssCommand extends ContainerAwareCommand {

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var LockssClient
     */
    protected $client;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(EntityManagerInterface $em, LockssClient $client, LoggerInterface $logger) {
        parent::__construct();
        $this->client = $client;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->addOption('box', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "Optional. One or more box IDs to contact.", array());
    }

    /**
     * @return Collection|Box[]
     */
    protected function getBoxes($boxIds = array()) {
        if ($boxIds && count($boxIds)) {
            return $this->em->getRepository(Box::class)->findBy(array(
                        'id' => $boxIds
            ));
        }
        return $this->em->getRepository(Box::class)->findAll();
    }

    /**
     * @return Collection|Box[]
     */
    protected function getAus($auIds = array()) {
        if ($auIds && count($auIds)) {
            return $this->em->getRepository(Au::class)->findBy(array(
                        'id' => $auIds
            ));
        }
        return $this->em->getRepository(Au::class)->findAll();
    }
}
