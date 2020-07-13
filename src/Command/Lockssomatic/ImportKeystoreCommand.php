<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Lockssomatic;

use App\Entity\Pln;
use App\Services\FilePaths;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import a java keystore for use in a PLN.
 */
class ImportKeystoreCommand extends Command {
    /**
     * Doctrine instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * File path service.
     *
     * @var FilePaths
     */
    private $filePaths;

    /**
     * Build the import keystore command.
     */
    public function __construct(EntityManagerInterface $em, FilePaths $filePaths) {
        $this->em = $em;
        $this->filePaths = $filePaths;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('lom:import:keystore');
        $this->setDescription('Import/replace a keystore for a PLN.');
        $this->addArgument('plnId', InputArgument::REQUIRED, 'Pln database ID');
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to the keystore file.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $plnId = $input->getArgument('plnId');
        $pln = $this->em->find(Pln::class, $plnId);
        if ( ! $pln) {
            $output->writeln("Cannot find pln {$plnId}.");

            return 1;
        }
        $path = $input->getArgument('path');
        $type = mime_content_type($path);
        if ( ! in_array($type, Pln::KEYSTORE_MIMETYPES, true)) {
            $output->writeln("File does not look like a keystore. Mime type is {$type}");
        }
        $basename = basename($path);
        if ( ! preg_match('/^[a-zA-Z0-9 .-]+\.keystore$/', $basename)) {
            $output->writeln("File does not look like a keystore. File name is {$basename}");
        }
        $dir = $this->filePaths->getLockssKeystoreDir($pln);
        if ( ! file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $newPath = $dir . '/' . $basename;
        copy($path, $newPath);
        $pln->setKeystore($newPath);
        $this->em->flush();
        return 0;
    }
}
