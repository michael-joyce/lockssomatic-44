<?php


namespace App\Services\Lockss;

use DOMDocument;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use SoapClient as BaseSoapClient;
use function GuzzleHttp\Psr7\parse_header;
use function GuzzleHttp\Psr7\parse_response;

class SoapClient extends BaseSoapClient {

    const SOAP = "http://schemas.xmlsoap.org/soap/envelope/";
    const LOCKSS = "http://content.ws.lockss.org/";
    const XOP="http://www.w3.org/2004/08/xop/include";
    const XSI="http://www.w3.org/2001/XMLSchema-instance";
    const XS="http://www.w3.org/2001/XMLSchema";

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $isMultipart;

    /**
     * @var Response[]
     */
    private $parts;

    public function __construct($wsdl, $options) {
        $options['trace'] = true;
        parent::__construct($wsdl, $options);
    }

    /**
     * @required
     */
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0) {
        $rawResult = parent::__doRequest($request, $location, $action, $version, $one_way);
        $rawHeaders = $this->__getLastResponseHeaders();

        $httpMessage = parse_response($rawHeaders . "\n" . $rawResult);
        $contentType = $httpMessage->getHeader('content-type');
        if ( str_starts_with($contentType[0], 'text/xml;')) {
            $this->isMultipart = false;
//            dump([$request, $location, $action]);
//            dump(['raw result is ', $rawResult]);
            return $rawResult;
        }
        $this->isMultipart = true;

        $parsedType = parse_header($contentType[0]);
        $boundary = $parsedType[0]['boundary'];

        $messageParts = array_map(function ($a) { return trim($a); }, explode("--{$boundary}", $rawResult));
        $filtered = array_filter($messageParts, function ($a) {
            return $a && $a !== '--';
        });

        foreach ($filtered as $m) {
            $response = parse_response("HTTP/1.1 200 OK\r\n" . $m);
            if($response->hasHeader('Content-ID')) {
                $id = preg_replace("/^<|>$/", '', $response->getHeader('Content-ID')[0]);
                $this->parts[$id] = $response;
            }
        }
        foreach($this->parts as $part) {
            if($part->hasHeader('Content-Type') &&
                str_starts_with($part->getHeader('Content-Type')[0], 'application/xop+xml')) {
                $body = $part->getBody()->getContents();
                $dom = new DOMDocument();
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput=true;
                $dom->loadXML($body);

                $nodeList = $dom->getElementsByTagName('dataHandler');
                if($nodeList->length) {
                    $element = $nodeList->item(0);
                    $parent = $element->parentNode;
                    $parent->removeChild($element);
                }
                return $dom->saveXML();
            }
        }
        $this->logger->error("Found no usable XML in response to $request, $location, $action.");
        return null;
    }

}
