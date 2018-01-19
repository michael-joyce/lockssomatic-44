<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\AuProperty;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Routing\RouterInterface;

/**
 * Description of AuPropertyGenerator
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuPropertyGenerator {

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, RouterInterface $router) {
        $this->logger = $logger;
        $this->em = $em;
        $this->router = $router;
    }

    /**
     * Build a property.
     *
     * @param Au $au
     * @param string $key
     * @param string $value
     * @param AuProperty $parent
     * @return AuProperty
     */
    public function buildProperty(Au $au, $key, $value = null, AuProperty $parent = null) {
        $property = new AuProperty();
        $property->setAu($au);
        $au->addAuProperty($property);
        $property->setPropertyKey($key);
        $property->setPropertyValue($value);
        if($parent) {
            $property->setParent($parent);
            $parent->addChild($property);
        }
        $this->em->persist($property);

        return $property;
    }
    


}
