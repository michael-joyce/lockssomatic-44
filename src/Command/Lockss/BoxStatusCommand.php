<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockss;

use App\Entity\Box;
use App\Entity\BoxStatus;
use App\Repository\BoxRepository;
use App\Repository\PlnRepository;
use App\Services\BoxNotifier;
use App\Services\Lockss\LockssService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BoxStatusCommand extends AbstractLockssCommand
{
    protected static $defaultName = 'lockss:box:status';

    public function __construct(LockssService $lockssService, ParameterBagInterface $params, ?string $name = null) {
        parent::__construct($lockssService, $params, $name);
    }

    protected function configure() : void {
        $this->addOption('pln', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional list of PLNs to check.');
        $this->addOption('dry-run', '-d', InputOption::VALUE_NONE, 'Do not update box status, just report results to console.');
        $this->setDescription('Check the status of one or more boxes in a PLN.');
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

    protected function toArray(stdClass $object) {
        $array = [];

        foreach ($object as $key => $value) {
            if ($value instanceof stdClass) {
                $array[$key] = $this->toArray($value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    protected function getStatus(Box $box) {
        $status = new BoxStatus();
        $status->setBox($box);

        $result = [];

        try {
            $result = $this->lockssService->boxStatus($box);
            $status->setSuccess(true);
        } catch (Exception $e) {
            $this->logger->error("{$box->getIpAddress()} - {$e->getMessage()}");
            $status->setSuccess(false);
            $status->setErrors($e->getMessage());
        }
        if ( ! is_array($result)) {
            $status->setData([$this->toArray($result)]);
        } else {
            $status->setData($result);
        }

        return $status;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $dryRun = $input->getOption('dry-run');
        $plnIds = $input->getOption('pln');

        foreach ($this->getBoxes($plnIds) as $box) {
            $this->logger->notice("Checking status on {$box->getHostname()}:{$box->getPort()}");
            $status = $this->getStatus($box);

            if ( ! $dryRun) {
                foreach ($status->getData() as $cache) {
                    if ($cache['percentageFull'] > $this->params->get('lom.boxstatus.sizewarning')) {
                        $percent = $cache['percentageFull'] * 100;
                        $this->logger->warning("{$box->getHostname()} is {$percent}% full");
                        $this->notifier->freeSpaceWarning($box, $status);
                    }
                }

                $this->em->persist($status);
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
    public function setBoxRepository(BoxRepository $repo) : void {
        $this->boxRepository = $repo;
    }

    /**
     * @required
     */
    public function setPlnRepository(PlnRepository $repo) : void {
        $this->plnRepository = $repo;
    }

    /**
     * @required
     */
    public function setNotifier(BoxNotifier $notifier) : void {
        $this->notifier = $notifier;
    }
}
