<?php

namespace AppBundle\Services;

use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Description of FileUploader
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class FileUploader {
    
    const PLUGIN = 'plugin';
    
    private $filePaths;
    
    public function __construct(FilePaths $filePaths) {
        $this->filePaths = $filePaths;
    }
    
    private function uploadJar(UploadedFile $file) {
        $filename = $file->getClientOriginalName();
        if( ! preg_match('/^[a-zA-Z0-9-_.]+\.jar$/', $filename)) {
            throw new Exception("Bad plugin upload filename: {$filename}.");
        }
        $file->move($this->filePaths->getPluginsDir(), $filename);
        return $filename;
    }
    
    public function upload(UploadedFile $file, $type) {
        switch($type) {
            case self::PLUGIN: 
                return $this->uploadJar($file);
            default:
                throw new Exception("Unknown uploaded file type {$type}.");
        }
    }
    
    //put your code here
}
