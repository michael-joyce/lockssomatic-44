<?php

namespace App\Command\Lockss;

use App\Entity\Box;
use App\Repository\PlnRepository;
use App\Services\Lockss\BoxStatusService;
use App\Utilities\LockssClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BoxStatusCommand extends Command
{
    protected static $defaultName = 'lockss:box:status';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    protected function configure()
    {
        $this->setDescription('Check the status of one or more boxes in a PLN.');
    }

    /**
     * @param EntityManagerInterface $em
     * @required
     */
    public function setEntityManager(EntityManagerInterface $em) {
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach($this->em->getRepository(Box::class)->findAll() as $box) {
            $client = LockssClient::create($box);
            $service = new BoxStatusService();
            $service->setClient($client);
            $result = $service->check($box);
            dump($result);
        }
        return 0;
    }
}
