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
        $this->addArgument('ids', InputArgument::IS_ARRAY, 'List of database IDs or UUIDs to fetch');
        $this->setDescription('Download deposits from the network');
    }

    protected function getDeposits($ids) {
        $repo = $this->em->getRepository(Deposit::class);

        return $repo->findByIdsQuery($ids);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $ids = $input->getArgument('ids');

        foreach ($this->getDeposits($ids)->execute() as $deposit) {
            $fh = $this->fetcher->fetch($deposit, 'lockss-u', 'lockss-p');
            $destination = fopen('tmp', 'wb');
            while($data = fread($fh, 64*1023)) {
                fwrite($destination, $data);
            }
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
