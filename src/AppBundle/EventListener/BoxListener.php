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
use Psr\Log\LoggerAwareTrait;

/**
 * Automatically update box IP addresses when they are changed.
 */
class BoxListener {
    
    use LoggerAwareTrait;
    
    /**
     * Look up an IP address for a hostname.
     * 
     * @return string|null
     *   IP address as a string or null if it cannot be resolved.
     */
    private function lookup($hostname) {
        $ip = gethostbyname($hostname);
        if ($ip === $hostname) {
            $this->logger->warning("Cannot find IP for {$hostname}.");
            return null;
        }
        return $ip;
    }
    
    /**
     * Automatically called before persisting a box.
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
        $entity->setIpAddress($ip);
    }
    
}
