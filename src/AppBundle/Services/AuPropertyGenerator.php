<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\AuProperty;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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

    /**
     * Generate a property string from an AU and a plugin vsprintf-style string.
     *
     * LOCKSS properties can be C-style vsprintf strings The entire thing is
     * encoded in a plugin's XML file as single string. It's complicated.
     *
     * Example: <string>"Preserved content, part %d", container_number</string>
     *
     * The format string is "Preserved content, part %d". The parameter list
     * is the single entry container_number, which is a property of the AU.
     *
     * @param Au $au
     *   Au to generate the string for.
     * @param string $value
     *   Format string.
     *
     * @return string
     *   Generated string.
     *
     * @throws Exception
     */
    public function generateString(Au $au, $value) {
        $matches = array();
        $formatStr = "";
        if (preg_match('/^"([^"]*)"/', $value, $matches)) {
            $formatStr = $matches[1];
        } else {
            throw new Exception("Property cannot be parsed: {$value}");
        }
        $parameterString = substr($value, strlen($formatStr) + 2);
        // substr/strlen skips the $formatstr part of the property.
        $parameters = preg_split('/, */', $parameterString);
        $values = array();
        foreach (array_slice($parameters, 1) as $parameterName) {
            $values[] = $au->getAuPropertyValue($parameterName);
        }
        $paramCount = preg_match_all('/%[a-zA-Z]/', $formatStr);
        if ($paramCount != count($values)) {
            throw new Exception("Wrong number of parameters for format string: {$formatStr}/{$paramCount}");
        }
        return vsprintf($formatStr, $values);
    }
    
    /**
     * Generate a symbol, according to a LOCKSS vstring-like property.
     *
     * LOCKSS plugin configuration symbols can be strings or lists. Ugh.
     *
     * @param Au $au
     *   Au for the symbol getting generated.
     * @param string $name
     *   Name of the symbol.
     *
     * @return string|array
     *   The symbol as a string or a list of strings.
     *
     * @throws Exception
     */
    public function generateSymbol(Au $au, $name) {
        $plugin = $au->getPlugin();
        if (!$plugin) {
            throw new Exception("Au requires plugin to generate $name.");
        }
        $property = $plugin->getProperty($name);
        if ($property === null) {
            throw new Exception("{$plugin->getName()} is missing parameter {$name}.");
        }
        if (!$property->isList()) {
            return $this->generateString($au, $property->getPropertyValue());
        }
        $values = array();
        foreach ($property->getPropertyValue() as $v) {
            $values[] = $this->generateString($au, $v);
        }

        return $values;
    }

}
