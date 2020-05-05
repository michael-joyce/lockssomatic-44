<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\EventListener;

use AppBundle\Entity\Box;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Psr\Log\LoggerAwareTrait;

/**
 * Description of BoxListener.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class BoxListener {
    use LoggerAwareTrait;

    /**
     * Look up the IP address of a host name.
     *
     * Returns null if the hostname cannot be found.
     *
     * @param mixed $hostname
     */
    private function lookup($hostname) {
        $ip = gethostbyname($hostname);
        if ($ip === $hostname) {
            $this->logger->warning("Cannot find IP for {$hostname}.");

            return;
        }

        return $ip;
    }

    /**
     * Automatically called before persisting a box to find its IP.
     */
    public function prePersist(LifecycleEventArgs $args) : void {
        $entity = $args->getEntity();
        if ( ! $entity instanceof Box) {
            return;
        }
        if ( ! $entity->getIpAddress()) {
            $ip = $this->lookup($entity->getHostname());
            $entity->setIpAddress($ip);
        }
    }

    /**
     * Automatically called before updating boxes.
     */
    public function preUpdate(LifecycleEventArgs $args) : void {
        $entity = $args->getEntity();
        if ( ! $entity instanceof Box) {
            return;
        }
        if ( ! $entity->getIpAddress()) {
            $ip = $this->lookup($entity->getHostname());
            $entity->setIpAddress($ip);
        }
    }
}
