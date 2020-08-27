<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockss;

use App\Repository\BoxRepository;
use App\Repository\PlnRepository;
use App\Services\BoxNotifier;
use Exception;
use App\Entity\Box;
use App\Entity\BoxStatus;
use App\Services\Lockss\LockssService;
use App\Utilities\LockssClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

class ListAusCommand extends Command {

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BoxRepository
     */
    private $boxRepository;

    /**
     * @var PlnRepository
     */
    private $plnRepository;

    protected static $defaultName = 'lockss:list:aus';

    public function __construct(string $name = null) {
        parent::__construct($name);
    }

    protected function configure() : void {
        $this->addOption('pln', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional list of PLNs to check.');
        $this->setDescription('List the AUs in the boxes in a network.');
    }

    protected function getBoxes($plnIds) {
        if ($plnIds) {
            $plns = $this->plnRepository->findBy(['id' => $plnIds]);
            return $this->boxRepository->findBy([
                'pln' => $plns,
                'active' => true,
            ]);
        }
        return $this->boxRepository->findBy(['active' => true]);
    }

    protected function listAus(Box $box) {
        $client = LockssClient::create($box);
        $service = new LockssService();
        $service->setClient($client);

        $result = [];
        try {
            $result = $service->listAus();
        }
        catch (Exception $e) {
            $this->logger->error("{$box->getIpAddress()} - {$e->getMessage()}");
        }
        return $result;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $plnIds = $input->getOption('pln');

        foreach ($this->getBoxes($plnIds) as $box) {
            $output->writeln($box);
            $output->writeln('');
            foreach($this->listAus($box) as $result) {
                $output->writeln("  " . $result->name);
                $output->writeln($result->id);
                $output->writeln('');
            }
        }

        return 0;
    }

    /**
     * @required
     */
    public function setLogger(LoggerInterface $soapLogger) : void {
        $this->logger = $soapLogger;
    }

    /**
     * @required
     */
    public function setBoxRepository(BoxRepository $repo) {
        $this->boxRepository = $repo;
    }

    /**
     * @required
     */
    public function setPlnRepository(PlnRepository $repo) {
        $this->plnRepository = $repo;
    }

}
