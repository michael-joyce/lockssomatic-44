<?php

namespace AppBundle\Command;

use AppBundle\Entity\Pln;
use AppBundle\Services\FilePaths;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import a java keystore for use in a PLN.
 */
class ImportKeystoreCommand extends ContainerAwareCommand
{
    
    /**
     * @var EntityManagerInterface
     */
    private $em;
    
    /**
     * @var FilePaths
     */
    private $filePaths;
    
    /**
     * Build the import keystore command.
     * 
     * @param EntityManagerInterface $em
     * @param FilePaths $filePaths
     */
    public function __construct(EntityManagerInterface $em, FilePaths $filePaths) {
        $this->em = $em;
        $this->filePaths = $filePaths;
        parent::__construct();
    }
    
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('lom:import:keystore')
            ->setDescription('Import/replace a keystore for a PLN.')
            ->addArgument('plnId', InputArgument::REQUIRED, 'Pln database ID')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to the keystore file.')
        ;
    }

    /**
     * {@inheritDoc}
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plnId = $input->getArgument('plnId');
        $pln = $this->em->find(Pln::class, $plnId);
        if( ! $pln) {
            $output->writeln("Cannot find pln {$plnId}.");
            return;
        }
        $path = $input->getArgument('path');
        $type = mime_content_type($path);
        if( ! in_array($type, Pln::KEYSTORE_MIMETYPES)) {
            $output->writeln("File does not look like a keystore. Mime type is {$type}");
        }
        $basename = basename($path);
        if( ! preg_match('/^[a-zA-Z0-9 .-]+\.keystore$/', $basename)) {
            $output->writeln("File does not look like a keystore. File name is {$basename}");
        }
        $dir = $this->filePaths->getLockssKeystoreDir($pln);
        if( !file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $newPath = $dir . '/' . $basename;
        copy($path, $newPath);
        $pln->setKeystore($newPath);
        $this->em->flush();
    }

}
