<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\EventListener;

use AppBundle\Entity\Box;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;

/**
 * Description of BoxListener.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class BoxListener {
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     *
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    
    /**
     * 
     */
    private function lookup($hostname) {
        $ip = gethostbyname($hostname);
        if ($ip === $hostname) {
            $this->logger->warning("Cannot find IP for {$hostname}.");
        }
        return $ip;
    }
    
    /**
     *
     */
    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        if (!$entity instanceof Box) {
            return;
        }
        if (!$entity->getIpAddress()) {
            $ip = $this->lookup($entity->getHostname());
            $entity->setIpAddress($ip);
        }
    }
    
    /**
     * Automatically called before updating boxes.
     */
    public function preUpdate(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        if (!$entity instanceof Box) {
            return;
        }
        $ip = $this->lookup($entity->getHostname());
        if ($ip === $entity->getIpAddress()) {
            return;
        }
        $this->logger->warning("Updating IP address for box {$entity->getHostname()} to {$ip}.");
        $entity->setIpAddress($ip);
    }
    
}
