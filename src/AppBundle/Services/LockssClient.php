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
use AppBundle\Entity\Deposit;
use BeSimple\SoapCommon\Helper;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerAwareTrait;
use ReflectionClass;

/**
 * Description of LockssClient.
 */
class LockssClient {

    use LoggerAwareTrait;

    const GUZZLE_OPTS = array(
        'allow_redirects' => true,
        'headers' => array(
            'User-Agent' => 'LOCKSSOMatic 1.0; http://pkp.sfu.ca',
        ),
        'decode_content' => false,
    );

    // getAuStatus
    // isDaemonReady
    // queryRepositories
    // queryRepositorySpaces.
    const STATUS_SERVICE = 'ws/DaemonStatusService?wsdl';

    // Hash.
    const HASHER_SERVICE = 'ws/HasherService?wsdl';

    // isUrlCached
    // fetchFile.
    const CONTENT_SERVICE = 'ws/ContentService?wsdl';

    /**
     * @var AuManager
     */
    private $auManager;

    /**
     * @var array
     */
    private $errors;

    /**
     * @var SoapClientBuilder
     */
    private $builder;

    public function __construct(AuManager $auManager, SoapClientBuilder $builder) {
        $this->auManager = $auManager;
        $this->errors = array();
        $this->builder = $builder;
    }

    public function setSoapClientBuilder($builder) {
        $this->builder = $builder;
    }

    public function errorHandler($errno, $errstr, $errfile, $errline) {
        $this->errors[] = implode(':', ['Error', $errstr]);
    }

    public function exceptionHandler(Exception $e) {
        $reflection = new ReflectionClass($e);
        $this->errors[] = implode(':', [
            $reflection->getShortName(),
            $e->getCode(),
            $e->getMessage()
        ]);
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

    /**
     * Call $method in the $service URL for $box.
     *
     * @return mixed
     */
    public function call(Box $box, $service, $method, $params = array(), $soapOptions = array()) {
        set_error_handler(array($this, 'errorHandler'), E_ALL);
        set_exception_handler(array($this, 'exceptionHandler'));

        $wsdl = "{$box->getWebServiceProtocol()}://{$box->getIpAddress()}:{$box->getWebServicePort()}/{$service}";
        $auth = array(
            'login' => $box->getPln()->getUsername(),
            'password' => $box->getPln()->getPassword(),
        );
        // $response must be defined outside the try.
        $response = null;
        try {
            $client = $this->builder->build($wsdl, $auth, $soapOptions);
            $response = $client->$method($params);
            print("called {$method} of {$service}.");
            print(print_r($response, true));
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }
        restore_error_handler();
        set_error_handler('var_dump', 0);
        @trigger_error('');

        restore_error_handler();
        restore_exception_handler();
        if ($response) {
            return $response->return;
        }
        return null;
    }

    /**
     * Check if the box is up and running and ready to communicate.
     *
     * @return bool
     */
    public function isDaemonReady(Box $box) {
        return $this->call($box, self::STATUS_SERVICE, 'isDaemonReady');
    }

    /**
     * Check on the status of an AU.
     *
     * @return array
     *  key => value pairs.
     */
    public function getAuStatus(Box $box, Au $au) {
        if (!$this->isDaemonReady($box)) {
            return;
        }
        $auid = $this->auManager->generateAuidFromAu($au);
        $obj = $this->call($box, self::STATUS_SERVICE, 'getAuStatus', array(
                'auId' => $auid,
        ));
        return get_object_vars($obj);
    }

    /**
     * Fetch a list of the URLs preserved by $box in $au.
     *
     * @return array
     *  URLs as strings.
     */
    public function getAuUrls(Box $box, Au $au) {
        if (!$this->isDaemonReady($box)) {
            return;
        }
        $auid = $this->auManager->generateAuidFromAu($au);
        return $this->call($box, self::STATUS_SERVICE, 'getAuUrls', array(
                'auId' => $auid,
        ));
    }

    /**
     * List the repositories on $box.
     *
     * @todo this method is unused. Maybe it should be removed. Most of the
     * information is available in queryRepositorySpaces().
     *
     * @return array
     *   Array of arrays with key => value pairs.
     */
    public function queryRepositories(Box $box) {
        if (!$this->isDaemonReady($box)) {
            return;
        }
        $list = $this->call($box, self::STATUS_SERVICE, 'queryRepositories', array(
                'repositoryQuery' => 'SELECT *',
        ));
        $response = array();
        foreach($list as $obj) {
            $response[] = get_object_vars($obj);
        }
        return $response;
    }

    /**
     * Check the available space on $box.
     *
     * @return array
     *   Array of arrays with key => value pairs.
     */
    public function queryRepositorySpaces(Box $box) {
        if (!$this->isDaemonReady($box)) {
            return;
        }
        $list = $this->call($box, self::STATUS_SERVICE, 'queryRepositorySpaces', array(
                'repositorySpaceQuery' => 'SELECT *',
        ));
        $response = array();
        foreach($list as $obj) {
            $response[] = get_object_vars($obj);
        }
        return $response;
    }

    /**
     * Fetches the hash of a content URL from a box.
     *
     * May return null if the item hasn't been preserved or if the box isn't
     * responding.
     *
     * @param Box $box
     * @param Deposit $deposit
     *
     * @return string|null
     */
    public function hash(Box $box, Deposit $deposit) {
        if (!$this->isUrlCached($box, $deposit)) {
            return;
        }
        $auid = $this->auManager->generateAuidFromAu($deposit->getAu(), true);
        $response = $this->call($box, self::HASHER_SERVICE, 'hash', array(
            'hasherParams' => array(
                'recordFilterStream' => true,
                'hashType' => 'V3File',
                'algorithm' => $deposit->getChecksumType(),
                'url' => $deposit->getUrl(),
                'auId' => $auid,
            ),
        ));

        $block = $response->blockFileDataHandler;
        $lines = array_values(array_filter(explode("\n", $block), function ($s) {
                return strlen($s) > 0 && $s[0] !== '#';
            }));
        if (count($lines) !== 1) {
            return null;
        }
        list($checksum, $url) = preg_split("/\s+/", $lines[0]);

        return strtoupper($checksum);
    }

    /**
     * Check if $box has cached $deposit yet.
     *
     * @return bool
     */
    public function isUrlCached(Box $box, Deposit $deposit) {
        if (!$this->isDaemonReady($box)) {
            return;
        }
        $auid = $this->auManager->generateAuidFromAu($deposit->getAu());
        return $this->call($box, self::CONTENT_SERVICE, 'isUrlCached', array(
                'url' => $deposit->getUrl(),
                'auId' => $auid,
                ), array(
                'attachment_type' => Helper::ATTACHMENTS_TYPE_MTOM,
        ));
    }

    /**
     * Download a content item from a lockss box.
     *
     * This can't use the normal SOAP api because the SOAP libraries all
     * try to store the data in memory rather than streaming it to a temporary
     * file.
     *
     * @param Box $box
     * @param Deposit $deposit
     *
     * @return resource
     */
    public function fetchFile(Box $box, Deposit $deposit) {
        if (!$this->isDaemonReady($box)) {
            return;
        }
        if( ! $this->isUrlCached($box, $deposit)) {
            return;
        }
        $client = new Client();
        $baseUrl = "http://{$box->getHostname()}:{$box->getPln()->getContentPort()}/ServeContent";
        $fh = tmpfile();
        $options = array_merge(self::GUZZLE_OPTS, array(
            'query' => [
                'url' => $deposit->getUrl(),
            ],
        ));

        $response = $client->get($baseUrl, $options);
        $body = $response->getBody();
        while (($data = $body->read(64 * 1024))) {
            fwrite($fh, $data);
        }
        rewind($fh);
        return $fh;
    }

}
