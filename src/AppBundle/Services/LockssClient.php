<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\Box;
use AppBundle\Entity\Deposit;
use BeSimple\SoapCommon\Helper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareTrait;
use ReflectionClass;

/**
 * Client to interact with the LOCKSS daemon.
 */
class LockssClient {
    use LoggerAwareTrait;

    /**
     * Default http options.
     *
     * Not used for the SOAP calls, just the content fetch command.
     */
    public const GUZZLE_OPTS = [
        'allow_redirects' => true,
        'headers' => [
            'User-Agent' => 'LOCKSSOMatic 1.0; http://pkp.sfu.ca',
        ],
        'decode_content' => false,
    ];

    /**
     * URL suffix for the status service.
     *
     * Used for calls to getAuStatus, isDaemonReady, queryRepositories and
     * queryRepositorySpaces.
     */
    public const STATUS_SERVICE = 'ws/DaemonStatusService?wsdl';

    /**
     * URL suffix for the hasher service.
     */
    public const HASHER_SERVICE = 'ws/HasherService?wsdl';

    /**
     * URL suffix for the content service.
     *
     * Used for calls to isUrlCached.
     */
    public const CONTENT_SERVICE = 'ws/ContentService?wsdl';

    /**
     * Au manager service.
     *
     * @var AuManager
     */
    private $auManager;

    /**
     * List of errors in the most recent SOAP call.
     *
     * @var array
     */
    private $errors;

    /**
     * SOAP client builder service.
     *
     * @var SoapClientBuilder
     */
    private $builder;

    /**
     * Guzzle HTTP client.
     *
     * @var Client
     */
    private $httpClient;

    /**
     * Construct the LOCKSS client.
     *
     * This client is reusable.
     */
    public function __construct(AuManager $auManager, SoapClientBuilder $builder) {
        $this->auManager = $auManager;
        $this->errors = [];
        $this->builder = $builder;
        $this->httpClient = new Client();
    }

    /**
     * Set or override the soap client builder service.
     */
    public function setSoapClientBuilder(SoapClientBuilder $builder) : void {
        $this->builder = $builder;
    }

    /**
     * Set or override the http client.
     */
    public function setHttpClient(Client $client) : void {
        $this->client = $client;
    }

    /**
     * Error handler for SOAP errors.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     */
    public function errorHandler($errno, $errstr, $errfile, $errline) : void {
        $this->errors[] = implode(':', ['Error', $errstr]);
    }

    /**
     * Exception handler for the SOAP calls.
     */
    public function exceptionHandler(Exception $e) : void {
        $reflection = new ReflectionClass($e);
        $this->errors[] = implode(':', [
            $reflection->getShortName(),
            $e->getCode(),
            $e->getMessage(),
        ]);
    }

    /**
     * Fetch a list of errors during the most recent SOAP call.
     *
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Clear error list.
     */
    public function clearErrors() : void {
        $this->errors = [];
    }

    /**
     * Check if the most recent SOAP call generated errors.
     *
     * @return bool
     */
    public function hasErrors() {
        return count($this->errors) > 0;
    }

    /**
     * Call $method in the $service URL for $box.
     *
     * The caller should $client->clearErrors() beforehand.
     *
     * Calls out $method in $service with parameters $params and SOAP options
     * in $soapOptions.
     *
     * @param string $service
     * @param string $method
     *
     * @return mixed
     */
    public function call(Box $box, $service, $method, array $params = [], array $soapOptions = []) {
        set_error_handler([$this, 'errorHandler'], E_ALL);
        set_exception_handler([$this, 'exceptionHandler']);

        $wsdl = "{$box->getWebServiceProtocol()}://{$box->getIpAddress()}:{$box->getWebServicePort()}/{$service}";
        $auth = [
            'login' => $box->getPln()->getUsername(),
            'password' => $box->getPln()->getPassword(),
        ];
        // $response must be defined outside the try.
        $response = null;

        try {
            $client = $this->builder->build($wsdl, $auth, $soapOptions);
            $response = $client->{$method}($params);
            unset($client); // memory leak in BeSimpleSoapClient or SoapClient.
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
     * @return null|array
     */
    public function getAuStatus(Box $box, Au $au) {
        if ( ! $this->isDaemonReady($box)) {
            return;
        }
        $auid = $this->auManager->generateAuidFromAu($au);
        $obj = $this->call($box, self::STATUS_SERVICE, 'getAuStatus', [
            'auId' => $auid,
        ]);

        return get_object_vars($obj);
    }

    /**
     * Fetch a list of the URLs preserved by $box in $au.
     *
     * @return null|array
     */
    public function getAuUrls(Box $box, Au $au) {
        if ( ! $this->isDaemonReady($box)) {
            return;
        }
        $auid = $this->auManager->generateAuidFromAu($au);

        return $this->call($box, self::STATUS_SERVICE, 'getAuUrls', [
            'auId' => $auid,
        ]);
    }

    /**
     * Check the available space on $box.
     *
     * @return null|array
     */
    public function queryRepositorySpaces(Box $box) {
        if ( ! $this->isDaemonReady($box)) {
            return;
        }
        $list = $this->call($box, self::STATUS_SERVICE, 'queryRepositorySpaces', [
            'repositorySpaceQuery' => 'SELECT *',
        ]);
        $response = [];
        foreach ($list as $obj) {
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
     * @return null|string
     */
    public function hash(Box $box, Deposit $deposit) {
        if ( ! $this->isUrlCached($box, $deposit)) {
            return;
        }
        $auid = $this->auManager->generateAuidFromAu($deposit->getAu(), true);
        $response = $this->call($box, self::HASHER_SERVICE, 'hash', [
            'hasherParams' => [
                'recordFilterStream' => true,
                'hashType' => 'V3File',
                'algorithm' => $deposit->getChecksumType(),
                'url' => $deposit->getUrl(),
                'auId' => $auid,
            ],
        ]);

        $block = $response->blockFileDataHandler;
        $lines = array_values(array_filter(explode("\n", $block), function ($s) {
            return strlen($s) > 0 && '#' !== $s[0];
        }));
        if (1 !== count($lines)) {
            return;
        }
        list($checksum, $url) = preg_split('/\\s+/', $lines[0]);

        return strtoupper($checksum);
    }

    /**
     * Check if $box has cached $deposit yet.
     *
     * @return bool
     */
    public function isUrlCached(Box $box, Deposit $deposit) {
        if ( ! $this->isDaemonReady($box)) {
            return false;
        }
        $auid = $this->auManager->generateAuidFromAu($deposit->getAu());

        return $this->call(
            $box,
            self::CONTENT_SERVICE,
            'isUrlCached',
            [
                'url' => $deposit->getUrl(),
                'auId' => $auid,
            ],
            [
                'attachment_type' => Helper::ATTACHMENTS_TYPE_MTOM,
            ]
        );
    }

    /**
     * Download a content item from a lockss box.
     *
     * This can't use the normal SOAP api because the SOAP libraries all
     * try to store the data in memory rather than streaming it to a temporary
     * file.
     *
     * @return null|array
     */
    public function fetchFile(Box $box, Deposit $deposit) {
        if ( ! $this->isDaemonReady($box)) {
            return;
        }
        if ( ! $this->isUrlCached($box, $deposit)) {
            return;
        }
        $baseUrl = "http://{$box->getHostname()}:{$box->getPln()->getContentPort()}/ServeContent";
        $fh = tmpfile();
        $options = array_merge(self::GUZZLE_OPTS, [
            'query' => [
                'url' => $deposit->getUrl(),
            ],
        ]);

        try {
            $response = $this->client->get($baseUrl, $options);
            $body = $response->getBody();
            while (($data = $body->read(64 * 1024))) {
                fwrite($fh, $data);
            }
            rewind($fh);

            return $fh;
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $this->exceptionHandler(new Exception($e->getMessage() . "\n" . $e->getResponse()->getBody()));
            }
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }
    }
}
