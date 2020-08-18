<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockss;

use App\Entity\Box;
use App\Services\Lockss\BoxStatusService;
use App\Utilities\LockssClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BoxStatusCommand extends Command {
    /**
     * @var EntityManagerInterface
     */
    private $em;
    protected static $defaultName = 'lockss:box:status';

    protected function configure() : void {
        $this->setDescription('Check the status of one or more boxes in a PLN.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        foreach ($this->em->getRepository(Box::class)->findAll() as $box) {
            $client = LockssClient::create($box);
            $service = new BoxStatusService();
            $service->setClient($client);
            $result = $service->check($box);
            dump($result);
        }

        return 0;
    }

    /**
     * @required
     */
    public function setEntityManager(EntityManagerInterface $em) : void {
        $this->em = $em;
    }
}
