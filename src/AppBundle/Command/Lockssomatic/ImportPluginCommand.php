<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Command\Lockssomatic;

use AppBundle\Services\FilePaths;
use AppBundle\Services\PluginImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

/**
 * Import one or more LOCKSS plugins.
 */
class ImportPluginCommand extends ContainerAwareCommand {
    /**
     * Doctrine instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Importer service.
     *
     * @var PluginImporter
     */
    private $importer;

    /**
     * Calculate file paths and locations.
     *
     * @var FilePaths
     */
    private $filePaths;

    /**
     * Build the import plugin command.
     */
    public function __construct(EntityManagerInterface $em, PluginImporter $importer, FilePaths $filePaths) {
        $this->em = $em;
        $this->importer = $importer;
        $this->filePaths = $filePaths;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('lom:import:plugin');
        $this->setDescription('Import one or more LOCKSS .jar plugins.');
        $this->addArgument('files', InputArgument::IS_ARRAY, 'List of .jar files');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : void {
        $files = $input->getArgument('files');
        foreach ($files as $file) {
            $output->writeln($file);
            $type = mime_content_type($file);
            if ( ! in_array($type, PluginImporter::MIMETYPES, true)) {
                $output->writeln("{$file} does not look like a .jar file: Mime type {$type} is unexpected.");

                continue;
            }
            if ( ! preg_match('/^[a-zA-Z0-9 .-]+\.jar$/', basename($file))) {
                $output->writeln("{$file} does not look like a Java .jar file. File name is strange. Skipped.");

                continue;
            }

            $zipArchive = new ZipArchive();
            $res = $zipArchive->open($file);
            if (true !== $res) {
                $output->writeln("Cannot open {$file} as a zip archive: " . $res);

                continue;
            }

            try {
                $plugin = $this->importer->import($zipArchive, false);
            } catch (Exception $e) {
                $output->writeln("Cannot import {$file}: {$e->getMessage()}.");

                continue;
            }
            $filename = basename($file, '.jar') . '-v' . $plugin->getVersion() . '.jar';
            if ( ! file_exists($this->filePaths->getPluginsDir())) {
                mkdir($this->filePaths->getPluginsDir(), 0777, true);
            }
            $path = $this->filePaths->getPluginsDir() . '/' . $filename;
            copy($file, $path);
            $plugin->setPath($path);
            $this->em->flush();
        }
    }
}
