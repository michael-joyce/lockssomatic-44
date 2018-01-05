<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Box;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Bridge\Monolog\Logger;

/**
 * Description of BoxIpUpdater
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class BoxIpUpdater {
    
    /**
     * @var Logger
     */
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    private function lookup($hostname) {
        $ip = gethostbyname($hostname);
        if($ip === $hostname) {
            $this->logger->warning("Cannot find IP for {$hostname}.");
        }
        return $ip;
    }
    
    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();        
        if( ! $entity instanceof Box) {
            return;
        }
        if(! $entity->getIpAddress()) {
            $ip = $this->lookup($entity->getHostname());
            $entity->setIpAddress($ip);
        }
    }
    
    public function preUpdate(LifecycleEventArgs $args) {
        $entity = $args->getEntity();        
        if( ! $entity instanceof Box) {
            return;
        }
        $ip = $this->lookup($entity->getHostname());
        if($ip === $entity->getIpAddress()) {
            return;
        }
        $this->logger->warning("Updating IP address for box {$entity->getHostname()} to {$ip}.");
        $entity->setIpAddress($ip);
    }
    
}
