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
use BeSimple\SoapClient\SoapClient;
use BeSimple\SoapCommon\Cache;
use BeSimple\SoapCommon\Helper;
use Exception;
use ReflectionClass;

/**
 * Description of LockssClient
 */
class LockssClient {

    /**
     * Default options for SOAP clients.
     */
    const DEFAULT_OPTS = array(
        'soap_version' => SOAP_1_1,
        'cache_wsdl' => Cache::TYPE_NONE,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        'trace' => true,
        'exceptions' => true,
        'user_agent' => 'LOCKSSOMatic 1.0',
        'authentication' => SOAP_AUTHENTICATION_BASIC,
    );
    
    // getAuStatus
    // isDaemonReady
    // queryRepositories
    // queryRepositorySpaces
    const STATUS_SERVICE = 'ws/DaemonStatusService?wsdl';
    // hash
    const HASHER_SERVICE = 'ws/HasherService?wsdl';
    // isUrlCached
    // fetchFile
    const CONTENT_SERVICE = 'ws/ContentService?wsdl';

    /**
     * @var AuIdGenerator
     */
    private $auIdGenerator;
    private $errors;

    public function __construct(AuIdGenerator $auIdGenerator) {
        $this->auIdGenerator = $auIdGenerator;
        $this->errors = array();
    }

    public function errorHandler($errno, $errstr, $errfile, $errline) {
        $this->errors[] = implode(':', ['Error', $errstr]);
    }

    public function exceptionHandler(Exception $e) {
        $reflection = new ReflectionClass($e);
        $this->errors[] = implode(':', [$reflection->getShortName(), $e->getCode(), $e->getMessage()]);
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

    public function call(Box $box, $service, $method, $params = array(), $soapOptions = array()) {
        set_error_handler(array($this, 'errorHandler'), E_ALL);
        set_exception_handler(array($this, 'exceptionHandler'));

        $wsdl = "{$box->getWebServiceProtocol()}://{$box->getIpAddress()}:{$box->getWebServicePort()}/{$service}";
        $options = array_merge(self::DEFAULT_OPTS, $soapOptions, array(
            'login' => $box->getPln()->getUsername(),
            'password' => $box->getPln()->getPassword(),
        ));

        $client = null;
        $response = null;
        try {
            $client = @new SoapClient($wsdl, $options);
            if ($client) {
                $response = $client->$method($params);
            }
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }
        restore_error_handler();
        set_error_handler('var_dump', 0);
        @trigger_error('');

        restore_error_handler();
        restore_exception_handler();
        if($response) {
            return $response->return;
        }
        return null;
    }

    public function isDaemonReady(Box $box) {
        return $this->call($box, self::STATUS_SERVICE, 'isDaemonReady');
    }

    public function getAuStatus(Box $box, Au $au) {
        if ($this->isDaemonReady($box)) {
            $auid = $this->auIdGenerator->fromAu($au);
            return $this->call($box, self::STATUS_SERVICE, 'getAuStatus', array(
                        'auId' => $auid,
            ));
        }
    }

    public function queryRepositories(Box $box) {
        if ($this->isDaemonReady($box)) {
            return $this->call($box, self::STATUS_SERVICE, 'queryRepositories', array(
                        'repositoryQuery' => 'SELECT *',
            ));
        }
    }

    public function queryRepositorySpaces(Box $box) {
        if ($this->isDaemonReady($box)) {
            return $this->call($box, self::STATUS_SERVICE, 'queryRepositorySpaces', array(
                        'repositorySpaceQuery' => 'SELECT *',
            ));
        }
    }

    public function hash(Box $box, Content $content) {
        if ($this->isDaemonReady($box)) {
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
    }

    public function isUrlCached(Box $box, Content $content) {
        if ($this->isDaemonReady($box)) {
            $auid = $this->auIdGenerator->fromAu($content->getAu());
            return $this->call($box, self::CONTENT_SERVICE, 'isUrlCached', array(
                        'url' => $content->getUrl(),
                        'auId' => $auid,
            ), array(
                        'attachment_type' => Helper::ATTACHMENTS_TYPE_MTOM,
            ));
        }
    }

    public function fetchFile(Box $box, Content $content) {
        throw new \Exception("NOT IMPLEMENTED.");
        if ($this->isDaemonReady($box)) {
            $auid = $this->auIdGenerator->fromAu($content->getAu());
            return $this->call($box, self::CONTENT_SERVICE, 'fetchFile', array(
                        'url' => $content->getUrl(),
                        'auId' => $auid,
            ));
        }
    }

}
