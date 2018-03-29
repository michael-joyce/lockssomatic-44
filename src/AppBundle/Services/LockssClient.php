<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Box;
use AppBundle\Utilities\LockssSoapClient;

/**
 * Description of LockssClient
 */
class LockssClient {

    // getAuStatus
    // isDaemonReady
    // queryRepositories
    // queryRepositorySpaces
    // getAuUrls
    const STATUS_SERVICE = '/ws/DaemonStatusService?wsdl';
    // hash
    const HASHER_SERVICE = '/ws/HasherService?wsdl';
    // isUrlCached
    // fetchFile
    // isUrlVersionCached
    // getVersions
    // fetchVersionedFile
    const CONTENT_SERVICE = '/ws/ContentService?wsdl';

    private $errors;

    public function __construct() {
        $this->errors = array();
    }

    public function getErrors() {
        return $this->errors;
    }

    public function clearErrors() {
        $this->errors = array();
    }

    public function hasErrors() {
        return count($this->errors) > 0;
    }
    
    public function isDaemonReady(Box $box) {
        $wsdl = $box->getUrl() . self::STATUS_SERVICE;
        try {
            $client = new LockssSoapClient($wsdl, array(
                'logins' => $box->getPln()->getUsername(),
                'password' => $box->getPln()->getPassword()
            ));
            $result = $client->isDaemonReady();
            return $result->return;
        } catch (\Exception $e) {
            if($client) {
                foreach($client->getErrors() as $e) {
                    $this->errors[] = $e;
                }
            }
            $this->errors[] = $e->getCode() . ":" . $e->getMessage();
            return false;
        }
    }

}
