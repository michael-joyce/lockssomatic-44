<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\EventListener;

use AppBundle\Entity\Plugin;
use AppBundle\Services\FilePaths;
use AppBundle\Services\FileUploader;
use AppBundle\Services\PluginImporter;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Description of PluginListener
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class PluginListener {
    
    private $fileUploader;
    
    private $filePaths;
    
    public function __construct(FileUploader $fileUploader, FilePaths $filePaths) {
        $this->fileUploader = $fileUploader;
        $this->filePaths = $filePaths;
    }
    
    public function prePersist(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        if( ! $entity instanceof Plugin) {
            return;
        }
        $this->uploadFile($entity);
    }
    
    public function preUpdate(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        if( ! $entity instanceof Plugin) {
            return;
        }
        $this->uploadFile($entity);
    }
    
    private function uploadFile(Plugin $plugin) {
        $jarFile = $plugin->getJarFile();
        if( ! $jarFile instanceof UploadedFile) {
            return;
        }
        $filename = $this->fileUploader->upload($jarFile, FileUploader::PLUGIN);
        $plugin->setPath($filename);
        $plugin->setJarFile(new File($this->filePaths->getPluginsDir() . '/' . $filename));
    }
}
