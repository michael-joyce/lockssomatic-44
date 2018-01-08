<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Plugin;
use Doctrine\Common\Persistence\ObjectManager;
use ZipArchive;

/**
 * Description of PluginImporter
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class PluginImporter {
    
    /**
     * Names of the prop strings in the plugin's XML configuration file which
     * should be imported.
     *
     * @var array
     */
    const PROP_STRINGS = array(
        'au_name',
        'au_permission_url',
        'plugin_crawl_type',
        'plugin_identifier',
        'plugin_name',
        'plugin_publishing_platform',
        'plugin_status',
        'plugin_version',
        'plugin_parent',
        'required_daemon_version',
    );

    /**
     * List of the property lists which should be imported.
     *
     * @var array
     */
    const PROP_LISTS = array(
        'au_crawlrules',
        'au_start_url',
    );
    
    private $em;
    
    private $filePaths;

//    public function __construct(ObjectManager $em, FilePaths $filePaths) {
//        $this->em = $em;
//        $this->filePaths = $filePaths;
//    }
    
    public function import(Plugin $plugin) {
//        $jarFile = $plugin->getJarFile();
//        $zipArchive = new ZipArchive();
//        $result = $zipArchive->open($jarFile->getPathname());
//        if($result !== true) {
//            throw new \Exception("Cannot open {$jarFile->getPathname()}. Error code {$result}.");
//        }
    }
    
    //put your code here
}
