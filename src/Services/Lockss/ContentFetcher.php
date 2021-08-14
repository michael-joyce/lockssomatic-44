<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Lockss;

use App\Entity\Box;
use App\Entity\Deposit;
use App\Utilities\Hasher;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * The LOCKSS SOAP API returns content in MTOM Atom messages. But support for
 * MTOM in any PHP SOAP client is missing, so this service uses raw HTTP
 * to get the content from the LOCKSS Content Service, which must be enabled
 * in the lockss.xml file. The default configuration enables the service.
 */
class ContentFetcher {
    /**
     * @var HttpClient;
     */
    private $client;

    private LoggerInterface $logger;

    private Hasher $hasher;

    /**
     * @param string $username
     * @param string $password
     *
     * @return ?resource
     */
    public function download(Deposit $deposit, Box $box, $username, $password) {
        $filepath = tempnam(sys_get_temp_dir(), 'lom-cfs-');
        $url = "http://{$box->getHostname()}:{$box->getPln()->getContentPort()}/ServeContent";

        try {
            $this->client->get($url, [
                'query' => ['url' => $deposit->getUrl()],
                'save_to' => $filepath,
                'auth' => [$username, $password],
            ]);
        } catch (RequestException $e) {
            $this->logger->error("Cannot download content: {$e->getMessage()}. URL was {$url}?url={$deposit->getUrl()}");

            return null;
        }

        $hash = $this->hasher->hash($filepath, $deposit->getChecksumType());
        if (strtoupper($hash) !== $deposit->getChecksumValue()) {
            $this->logger->error("Downloaded checksum for deposit {$deposit->getId()} from {$box->getHostName()} "
                . "does not match. Expected {$deposit->getChecksumType()} {$deposit->getChecksumValue()} but got {$hash}.");

            return null;
        }

        return fopen($filepath, 'rb');
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @throws Exception
     *
     * @return null|resource
     */
    public function fetch(Deposit $deposit, $username, $password) {
        if (1.0 !== $deposit->getAgreement()) {
            throw new Exception("Cannot download deposit when agreement {$deposit->getAgreement()} is less than 100%.");
        }
        $pln = $deposit->getAu()->getPln();
        $boxes = $pln->getActiveBoxes()->toArray();
        shuffle($boxes);

        foreach ($boxes as $box) {
            $fh = $this->download($deposit, $box, $username, $password);
            if ($fh) {
                return $fh;
            }
        }
        $this->logger->error("Cannot find matching content for deposit {$deposit->getId()} on any box.");
        return null;
    }

    /**
     * @required
     */
    public function setHttpClient(HttpClient $client) : void {
        $this->client = $client;
    }

    /**
     * @required
     */
    public function setLogger(LoggerInterface $logger) : void {
        $this->logger = $logger;
    }

    /**
     * @required
     */
    public function setHasher(Hasher $hasher) : void {
        $this->hasher = $hasher;
    }
}
