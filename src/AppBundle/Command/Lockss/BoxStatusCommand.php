<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Command\Lockss;

use AppBundle\Entity\Box;
use AppBundle\Entity\BoxStatus;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of DaemonStatusCommand
 */
class BoxStatusCommand extends AbstractLockssCommand {

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('lockss:box:status');
        $this->setDescription('Report the status of the boxes.');
        parent::configure();
    }
    
    public function getBoxStatus(Box $box) {
        $status = new BoxStatus();
        $this->em->persist($status);
        $status->setBox($box);
        $response = $this->client->queryRepositorySpaces($box);
        if( ! $response) {
            $status->setErrors($this->client->getErrors());
            $this->client->clearErrors();
            return $status;
        }
        $status->setSuccess(true);
        $status->setData($response);
        return $status;
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $boxes = $this->getBoxes($input->getOption('box'));
        foreach ($boxes as $box) {            
            $status = $this->getBoxStatus($box);
            if($status->getSuccess()) {
                continue;
            }
            $this->logger->error($status->getErrors());
        }
        $this->em->flush();
    }

}
