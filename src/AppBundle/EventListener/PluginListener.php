<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\EventListener;

use AppBundle\Entity\Plugin;
use AppBundle\Services\FileUploader;
use AppBundle\Services\PluginImporter;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Description of Plugin
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class PluginListener {
    
    /**
     * @var FileUploader
     */
    private $uploader;
    
    /**
     * @var PluginImporter
     */
    private $pluginImporter;
    
    public function __construct(FileUploader $uploader, PluginImporter $pluginImporter) {
        $this->uploader = $uploader;
        $this->pluginImporter = $pluginImporter;
    }

    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        if( ! $entity instanceof Plugin) {
            return;
        }
        $this->uploadFile($entity);
    }
    
    public function preUpdate(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        if( ! $entity instanceof Plugin) {
            return;
        }
        $this->uploadFile($entity);
    }
    
    public function postLoad(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        if( ! $entity instanceof Plugin) {
            return;
        }
        
    }
    
    private function uploadFile(Plugin $plugin) {
        $file = $plugin->getJarFile();
        if(! $file instanceof UploadedFile) {
            return;
        }
        $filename = $this->uploader->upload($file, 'plugin');
        $plugin->setFilename($filename);
        $this->pluginImporter->import($plugin);
    }
    
}
