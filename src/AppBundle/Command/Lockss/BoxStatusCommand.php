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
use AppBundle\Services\BoxNotifier;
use AppBundle\Services\LockssClient;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of DaemonStatusCommand.
 */
class BoxStatusCommand extends ContainerAwareCommand {

    /**
     * Warn if the cache is more than this percent full.
     *
     * @var float
     */
    private $sizeWarning;

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
     * Box notifier to send emails.
     *
     * @var BoxNotifier
     */
    private $notifier;

    /**
     * Build the command.
     *
     * @param float $sizeWarning
     * @param EntityManagerInterface $em
     * @param LockssClient $client
     * @param BoxNotifier $notifier
     */
    public function __construct($sizeWarning, EntityManagerInterface $em, LockssClient $client, BoxNotifier $notifier) {
        parent::__construct();
        $this->sizeWarning = $sizeWarning;
        $this->client = $client;
        $this->em = $em;
        $this->notifier = $notifier;
    }

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('lockss:box:status');
        $this->addOption('box', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "Optional. One or more box IDs to contact.", array());
        $this->setDescription('Report the status of the boxes.');
        parent::configure();
    }

    /**
     * Fetch a list of boxes to check.
     *
     * @return Collection|Box[]
     */
    protected function getBoxes($boxIds = array()) {
        if ($boxIds && count($boxIds)) {
            return $this->em->getRepository(Box::class)->findBy(array(
                        'id' => $boxIds,
            ));
        }
        return $this->em->getRepository(Box::class)->findAll();
    }

    /**
     * Get a box status frmo the LOCKSS client.
     *
     * @param Box $box
     *
     * @return BoxStatus
     */
    public function getBoxStatus(Box $box) {
        $status = new BoxStatus();
        $this->em->persist($status);
        $status->setBox($box);
        $response = $this->client->queryRepositorySpaces($box);
        if (!$response) {
            $status->setErrors($this->client->getErrors());
            $this->client->clearErrors();
            return $status;
        }
        $status->setSuccess(true);
        $status->setData($response);
        return $status;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $boxes = $this->getBoxes($input->getOption('box'));
        foreach ($boxes as $box) {
            $boxStatus = $this->getBoxStatus($box);
            if (!$boxStatus->getSuccess()) {
                $this->notifier->unreachable($box, $boxStatus);
                continue;
            }
            foreach ($boxStatus->getData() as $data) {
                if ($data['percentageFull'] > $this->sizeWarning) {
                    $this->notifier->freeSpaceWarning($box, $boxStatus);
                }
            }
        }
        $this->em->flush();
    }

}
