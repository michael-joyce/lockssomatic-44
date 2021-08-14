<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockss;

use App\Entity\Deposit;
use App\Services\Lockss\ContentFetcher;
use App\Services\Lockss\LockssService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FetchDepositCommand extends AbstractLockssCommand {
    private ContentFetcher $fetcher;

    protected static $defaultName = 'lockss:deposit:fetch';

    public function __construct(LockssService $lockssService, ParameterBagInterface $params, ?string $name = null) {
        parent::__construct($lockssService, $params, $name);
    }

    protected function configure() : void {
        $this->addArgument('id', InputArgument::REQUIRED, 'Database ID or UUID to fetch');
        $this->addArgument('file', InputArgument::REQUIRED, 'Location to store the deposit');
        $this->setDescription('Download one deposit from the network');
    }

    protected function getDeposits($ids) {
        $repo = $this->em->getRepository(Deposit::class);

        return $repo->findByIdsQuery($ids);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $id = $input->getArgument('id');
        /** @var Deposit $deposit */
        $deposit = $this->em->find(Deposit::class, $id);
        if( ! $deposit) {
            $deposit = $this->em->getRepository(Deposit::class)->findOneBy([
                'uuid' => $id,
            ]);
        }
        if( ! $deposit) {
            $output->writeln("Cannot find deposit with ID {$id}.");
            return 1;
        }

        $pln = $deposit->getAu()->getPln();
        $file = $input->getArgument('file');
        $fh = $this->fetcher->fetch($deposit, $pln->getUsername(), $pln->getPassword());
        if( ! $fh) {
            $output->writeln("Cannot download deposit {$id} from any box.");
            return 2;
        }
        $destination = fopen($file, 'wb');
        while($data = fread($fh, 64*1023)) {
            fwrite($destination, $data);
        }

        return 0;
    }

    /**
     * @required
     */
    public function setContentFetcher(ContentFetcher $fetcher) : void {
        $this->fetcher = $fetcher;
    }
}
