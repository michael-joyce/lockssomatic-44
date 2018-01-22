<?php

namespace AppBundle\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\AuProperty;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Routing\RouterInterface;

/**
 * Generate a LOCKSS archival unit property.
 */
class AuPropertyGenerator {

    /**
     * Psr\Log compatible logger for the service.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Doctrine entity manager.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * URL generator.
     *
     * @var RouterInterface
     */
    private $router;

    /**
     * Construct the service.
     *
     * @param LoggerInterface $logger
     *   Logger for warnings etc.
     * @param EntityManagerInterface $em
     *   Doctrine instance to persist properties.
     * @param RouterInterface $router
     *   URL generator for some additional properties.
     */
    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, RouterInterface $router) {
        $this->logger = $logger;
        $this->em = $em;
        $this->router = $router;
    }

    /**
     * Build a property.
     *
     * @param Au $au
     *   AU for which the property will be built.
     * @param string $key
     *   Name of the property.
     * @param string $value
     *   Value of the property.
     * @param AuProperty $parent
     *   Parent of the property.
     *
     * @return AuProperty
     *   The constructed property.
     */
    public function buildProperty(Au $au, $key, $value = null, AuProperty $parent = null) {
        $property = new AuProperty();
        $property->setAu($au);
        $au->addAuProperty($property);
        $property->setPropertyKey($key);
        $property->setPropertyValue($value);
        if ($parent) {
            $property->setParent($parent);
            $parent->addChild($property);
        }
        $this->em->persist($property);

        return $property;
    }
}
