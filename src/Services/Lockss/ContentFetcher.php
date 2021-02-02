<?php

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
 *
 * @package App\Services\Lockss
 */
class ContentFetcher {

    /**
     * @var HttpClient;
     */
    private $client;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var Hasher
     */
    private Hasher $hasher;

    /**
     * @param Deposit $deposit
     * @param string $username
     * @param string $password
     *
     * @return ?resource
     */
    public function download(Deposit $deposit, Box $box, $username = null, $password = null) {
        $filepath = tempnam(sys_get_temp_dir(), 'lom-cfs-');
        $client = new Client();
        $url = "http://{$box->getHostname()}:{$box->getPln()->getContentPort()}/ServeContent";
        try {
            $this->client->get($url, [
                'query' => ['url' => $deposit->getUrl()],
                'save_to' => $filepath,
            ]);
        } catch(RequestException $e) {
            $this->logger->error("Cannot download content from {$box->getHostName()}: {$e->getMessage()}");
            return null;
        }

        $hash = $this->hasher->hash($filepath, $deposit->getChecksumType());
        if($hash !== $deposit->getChecksumValue()) {
            $this->logger->error("Downloaded checksum for deposit {$deposit->getId()} from {$box->getHostName()} "
                . "does not match. Expected {$deposit->getChecksumType()} {$deposit->getChecksumValue()} but got {$hash}.");
            return null;
        }
        return fopen($filepath, 'rb');
    }

    /**
     * @param Deposit $deposit
     * @param ?string $username
     * @param ?string $password
     *
     * @return resource|null
     * @throws Exception
     */
    public function fetch(Deposit $deposit, $username = null, $password = null) {
        if ($deposit->getAgreement() !== 1.0) {
            throw new Exception("Cannot download deposit when agreement {$deposit->getAgreement()} is less than 100%.");
        }
        $pln = $deposit->getAu()->getPln();
        $boxes = $pln->getActiveBoxes()->toArray();
        shuffle($boxes);
        foreach($boxes as $box) {
            $fh = $this->download($deposit, $box);
            if($fh) {
                return $fh;
            }
        }
        $this->logger->error("Cannot find matching content for deposit {$deposit->getId()} on any box.");
        return null;
    }

    /**
     * @required
     * @param HttpClient $client
     */
    public function setHttpClient(HttpClient $client) {
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
     * @param Hasher $hasher
     */
    public function setHasher(Hasher $hasher) {
        $this->hasher = $hasher;
    }
}
