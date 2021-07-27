<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Lockss;

use DOMDocument;
use GuzzleHttp\Psr7\Header;
use GuzzleHttp\Psr7\Message;
use function GuzzleHttp\Psr7\parse_header;
use function GuzzleHttp\Psr7\parse_response;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use SoapClient as BaseSoapClient;

class SoapClient extends BaseSoapClient {
    public const SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';

    public const LOCKSS = 'http://content.ws.lockss.org/';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $isMultipart;

    /**
     * @var Response[]
     */
    private $parts;

    public function __construct($wsdl, $options) {
        $options['trace'] = true;
        $options['encoding'] = 'utf-8';
        $options['soap_version'] = SOAP_1_1;

        parent::__construct($wsdl, $options);
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0) {
        $this->logger->notice("Sending request to {$location}");
        $rawResult = parent::__doRequest($request, $location, $action, $version, $one_way);
        $rawHeaders = $this->__getLastResponseHeaders();

        $httpMessage = Message::parseResponse($rawHeaders . "\n" . $rawResult);
        $contentType = $httpMessage->getHeader('content-type');
        if (str_starts_with($contentType[0], 'text/xml;')) {
            $this->isMultipart = false;

            return $rawResult;
        }
        $this->isMultipart = true;

        $parsedType = Header::parse($contentType[0]);
        $boundary = $parsedType[0]['boundary'];

        $messageParts = array_map(fn ($a) => trim($a), explode("--{$boundary}", $rawResult));
        $filtered = array_filter($messageParts, fn ($a) => $a && '--' !== $a);

        foreach ($filtered as $m) {
            $response = Message::parseResponse("HTTP/1.1 200 OK\r\n" . $m);
            if ($response->hasHeader('Content-ID')) {
                $id = preg_replace('/^<|>$/', '', $response->getHeader('Content-ID')[0]);
                $this->parts[$id] = $response;
            }
        }

        foreach ($this->parts as $part) {
            if ($part->hasHeader('Content-Type') && str_starts_with($part->getHeader('Content-Type')[0], 'application/xop+xml')) {
                $body = $part->getBody()->getContents();
                $dom = new DOMDocument();
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                $dom->loadXML($body);

                $nodeList = $dom->getElementsByTagName('dataHandler');
                if ($nodeList->length) {
                    $element = $nodeList->item(0);
                    $parent = $element->parentNode;
                    $parent->removeChild($element);
                }

                return $dom->saveXML();
            }
        }
        $this->logger->error("Found no usable XML in response to {$request}, {$location}, {$action}.");
    }

    /**
     * This class isn't a service. Consumers of this class must call setLogger.
     *
     * @param LoggerInterface $soapLogger
     */
    public function setLogger(LoggerInterface $soapLogger) : void {
        $this->logger = $soapLogger;
    }
}
