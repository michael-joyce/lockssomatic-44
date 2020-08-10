<?php


namespace App\Utilities;

use App\Entity\Box;
use Exception;
use Laminas\Soap\Client;

/**
 * Class LockssClient
 *
 * @see https://docs.laminas.dev/laminas-soap/client/
 */
class LockssClient {

    /**
     * @var string
     */
    private $wsdl;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Box
     */
    private $box;

    /**
     * @param Box $box
     * @param string $service
     *
     * @return LockssClient
     */
    public static function create(Box $box, $service = 'DaemonStatusService') {
        $wsdl = "http://{$box->getHostname()}:{$box->getWebservicePort()}/ws/{$service}?wsdl";
        $lockssClient = new LockssClient();
        $lockssClient->box = $box;
        $lockssClient->wsdl = $wsdl;
        $lockssClient->options = [
            'login' => $box->getPln()->getUsername(),
            'password' => $box->getPln()->getPassword(),
            'encoding' => 'utf-8',
            'soap_version' => SOAP_1_1,
        ];
        return $lockssClient;

    }

    private function __construct() {
    }

    public function getOption($name) {
        if(isset($this->options[$name])) {
            return $name;
        }
        return null;
    }

    public function setOption($name, $value) {
        $this->options[$name] = $value;
    }

    public function call($method, $params = []) {
        $client = new Client($this->wsdl, $this->options);
        $readyResponse = $client->isDaemonReady();
        if($readyResponse->return !== true) {
            throw new Exception("Daemon on {$this->box->getHostname()} reports not ready.");
        }
        return $client->$method($params);
    }
}
