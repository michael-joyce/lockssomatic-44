<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Command\Lockss;

use AppBundle\Entity\Au;
use AppBundle\Services\LockssClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Query the URLs preserved in an AU.
 */
class AuUrlsCommand extends ContainerAwareCommand {
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
     */
    public function __construct(EntityManagerInterface $em, LockssClient $client) {
        parent::__construct();
        $this->client = $client;
        $this->em = $em;
    }

    /**
     * Configure the command.
     */
    protected function configure() : void {
        $this->setName('lockss:au:urls');
        $this->setDescription('Report the urls preserved in an AU.');
    }

    /**
     * Fetch a list of AUs to query from the database.
     *
     * @return Au[]|Collection
     *                         List of AUs to query.
     */
    protected function getAus() {
        return $this->em->getRepository(Au::class)->findAll();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output) : void {
        $aus = $this->getAus();
        foreach ($aus as $au) {
            $output->writeln($au->getId());
            foreach ($au->getPln()->getBoxes() as $box) {
                dump($this->client->getAuUrls($box, $au));
                foreach ($this->client->getErrors() as $e) {
                    $output->writeln($e);
                }
            }
        }
    }
}
