<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\Box;
use AppBundle\Entity\Content;
use AppBundle\Utilities\LockssSoapClient;
use Exception;

/**
 * Description of LockssClient
 */
class LockssClient {

    /**
     * @var AuIdGenerator
     */
    private $auIdGenerator;

    // getAuStatus
    // isDaemonReady
    // queryRepositories
    // queryRepositorySpaces
    const STATUS_SERVICE = '/ws/DaemonStatusService?wsdl';
    
    // hash
    const HASHER_SERVICE = '/ws/HasherService?wsdl';
    // isUrlCached
    // fetchFile
    const CONTENT_SERVICE = '/ws/ContentService?wsdl';

    private $errors;

    public function __construct(AuIdGenerator $auIdGenerator) {
        $this->auIdGenerator = $auIdGenerator;
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

    public function call($box, $service, $method, $params = array()) {
        $wsdl = $box->getUrl() . $service;
        $client = null;
        try {
            $client = new LockssSoapClient($wsdl, array(
                'login' => $box->getPln()->getUsername(),
                'password' => $box->getPln()->getPassword()
            ));
            $result = $client->$method($params);
            return $result->return;
        } catch (Exception $e) {
            if ($client) {
                foreach ($client->getErrors() as $e) {
                    $this->errors[] = $e;
                }
            }
            $this->errors[] = $e->getMessage();
            return null;
        }
    }

    public function isDaemonReady(Box $box) {
        return $this->call($box, self::STATUS_SERVICE, 'isDaemonReady');
    }

    public function getAuStatus(Box $box, Au $au) {
        $auid = $this->auIdGenerator->fromAu($au);
        return $this->call($box, self::STATUS_SERVICE, 'getAuStatus', array(
                    'auId' => $auid,
        ));
    }

    public function queryRepositories(Box $box) {
        return $this->call($box, self::STATUS_SERVICE, 'queryRepositories', array(
                    'repositoryQuery' => 'SELECT *',
        ));
    }

    public function queryRepositorySpaces(Box $box) {
        return $this->call($box, self::STATUS_SERVICE, 'queryRepositorySpaces', array(
                    'repositorySpaceQuery' => 'SELECT *',
        ));
    }

    public function hash(Box $box, Content $content) {
        $auid = $this->auIdGenerator->fromAu($content->getAu());
        return $this->call($box, self::HASHER_SERVICE, 'hash', array(
            'hasherParams' => array(
                'recordFilterStream' => true,
                'hashType' => 'V3File',
                'algorithm' => $content->getChecksumType(),
                'url' => $content->getUrl(),
                'auId' => $auid,
            ),
        ));        
    }

    public function isUrlCached(Box $box, Content $content) {
        $auid = $this->auIdGenerator->fromAu($content->getAu());
        return $this->call($box, self::CONTENT_SERVICE, 'isUrlCached', array(
                'url' => $content->getUrl(),
                'auId' => $auid,
            ));        
    }

    public function fetchFile(Box $box, Content $content) {
        throw new \Exception("NOT IMPLEMENTED.");
        $auid = $this->auIdGenerator->fromAu($content->getAu());
        return $this->call($box, self::CONTENT_SERVICE, 'fetchFile', array(
                'url' => $content->getUrl(),
                'auId' => $auid,
            ));        
    }

}
