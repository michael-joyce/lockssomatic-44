<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockss;

use App\Entity\Au;
use App\Entity\AuStatus;
use App\Entity\Pln;
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

class AuStatusCommand extends Command {
    /**
     * @var EntityManagerInterface
     */
    private $em;

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

    /**
     * @var BoxNotifier
     */
    private $notifier;

    /**
     * @var LockssService
     */
    private $lockssService;

    protected static $defaultName = 'lockss:au:status';

    public function __construct(LockssService $lockssService, string $name = null) {
        parent::__construct($name);
        $this->lockssService = $lockssService;
    }

    protected function configure() : void {
        $this->addOption('pln', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional list of PLNs to check.');
        $this->addOption('dry-run', '-d', InputOption::VALUE_NONE, 'Do not update box status, just report results to console.');
        $this->setDescription('Check the status of one or more AUs in a PLN.');
    }

    /**
     * @param $plnIds
     *
     * @return Pln[]
     */
    protected function getPlns($plnIds) {
        if( ! $plnIds) {
            return $this->plnRepository->findAll();
        }
        return $this->plnRepository->findBy(['id' => $plnIds]);
    }

    protected function getStatus(Au $au, Box $box) {
        $client = LockssClient::create($box);
        $this->lockssService->setClient($client);
        return $this->lockssService->auStatus($au);
    }

    protected function toArray(stdClass $object) {
        $array = [];
        foreach ($object as $key => $value) {
            if ($value instanceof stdClass) {
                $array[$key] = $this->toArray($value);
            }
            else {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $dryRun = $input->getOption('dry-run');
        $plnIds = $input->getOption('pln');

        foreach($this->getPlns($plnIds) as $pln) {
            $boxes = $pln->getActiveBoxes();
            foreach($pln->getAus() as $au) {
                $auStatus = new AuStatus();
                $auStatus->setAu($au);
                foreach($boxes as $box) {
                    try {
                        $status = $this->getStatus($au, $box);
                        $auStatus->addStatus($box, $this->toArray($status));
                    } catch (Exception $e) {
                        $auStatus->addError($box, $e->getMessage());
                    }
                }
                $this->em->persist($auStatus);
                $this->em->flush();
            }
        }

        return 0;
    }

    /**
     * @required
     */
    public function setEntityManager(EntityManagerInterface $em) : void {
        $this->em = $em;
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

    /**
     * @required
     */
    public function setNotifier(BoxNotifier $notifier) {
        $this->notifier = $notifier;
    }

}
