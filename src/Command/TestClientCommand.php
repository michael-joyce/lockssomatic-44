<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command;

use App\Entity\Box;
use App\Entity\Deposit;
use App\Services\Lockss\ContentFetcher;
use App\Services\Lockss\LockssService;
use App\Services\Lockss\SoapClient;
use App\Utilities\LockssClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestClientCommand extends Command {
    /**
     * @var EntityManagerInterface
     */
    private $em;

    protected static $defaultName = 'app:test:client';
    /**
     * @var ContentFetcher
     */
    private ContentFetcher $contentFetcher;

    /**
     * @var LockssService
     */
    private $lockssService;

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $deposit = $this->em->find(Deposit::class, 1);
        $box = $this->em->find(Box::class, 1);
        dump($this->lockssService->hash($box,$deposit));
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
    public function setLockssService(LockssService $lockssService) {
        $this->lockssService = $lockssService;
    }

    /**
     * @param ContentFetcher $contentFetcher
     * @required
     */
    public function setContentFetcher(ContentFetcher $contentFetcher) {
        $this->contentFetcher = $contentFetcher;
    }
}
