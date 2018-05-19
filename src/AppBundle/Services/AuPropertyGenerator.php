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
use AppBundle\Entity\Deposit;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Generate a LOCKSS archival unit property.
 */
class AuPropertyGenerator {

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
     * @param EntityManagerInterface $em
     *   Doctrine instance to persist properties.
     * @param RouterInterface $router
     *   URL generator for some additional properties.
     */
    public function __construct(EntityManagerInterface $em, RouterInterface $router) {
        $this->em = $em;
        $this->router = $router;
    }

    /**
     * Build a property.
     *
     * The property is persisted, but not flushed, to the database.
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
        $property->setPropertyKey($key);
        $property->setPropertyValue($value);
        if ($parent) {
            $property->setParent($parent);
            $parent->addChild($property);
        }
        $au->addAuProperty($property);
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

    /**
     * Generate the base properties, required for any AU.
     *
     * @param Au $au
     *   Archival unit to generate properties for.
     * @param AuProperty $root
     *   Root of the AU properties.
     * @param Deposit $deposit
     *   Deposit with the property values for the AU.
     */
    public function baseProperties(Au $au, AuProperty $root, Deposit $deposit) {
        $this->buildProperty($au, 'journalTitle', $deposit->getProperty('journalTitle'), $root);
        $this->buildProperty($au, 'title', 'LOCKSSOMatic AU ' . $au->getId() . ' ' . $deposit->getTitle(), $root);
        $this->buildProperty($au, 'plugin', $au->getPlugin()->getIdentifier(), $root);
        $this->buildProperty($au, 'attributes.publisher', $deposit->getProperty('publisher'), $root);
    }

    /**
     * Generate the configuration parameters for an AU.
     *
     * @param array $propertyNames
     *   List of property names to generate.
     * @param Au $au
     *   Archival unit to generate properties for.
     * @param AuProperty $root
     *   Root of the AU properties.
     * @param Deposit $deposit
     *   Deposit with the property values for the AU.
     */
    public function configProperties(array $propertyNames, Au $au, AuProperty $root, Deposit $deposit) {
        $manifestUrl = $this->router->generate('lockss_manifest', array(
            'plnId' => $au->getPln()->getId(),
            'ownerId' => $au->getContentProvider()->getContentOwner()->getId(),
            'providerId' => $au->getContentProvider()->getId(),
            'auId' => $au->getId(),
            ), UrlGeneratorInterface::ABSOLUTE_URL);

        foreach ($propertyNames as $index => $name) {
            switch ($name) {
                case 'manifest_url':
                    $value = $manifestUrl;
                    break;
                case 'permission_url':
                    $value = $au->getContentProvider()->getPermissionUrl();
                    break;
                case 'base_url':
                    $p = parse_url($deposit->getUrl());
                    $value = "{$p['scheme']}://{$p['host']}" . (isset($p['port']) ? ":{$p['port']}" : '');
                    break;
                default:
                    $value = $deposit->getProperty($name);
                    break;
            }
            $grouping = $this->buildProperty($au, "param.{$index}", null, $root);
            $this->buildProperty($au, 'key', $name, $grouping);
            $this->buildProperty($au, 'value', $value, $grouping);
        }
    }

    /**
     * Generate the content properties for the AU.
     *
     * @param Au $au
     *   Archival unit to generate properties for.
     * @param AuProperty $root
     *   Root of the AU properties.
     * @param Deposit $deposit
     *   Deposit with the property values for the AU.
     */
    public function contentProperties(Au $au, AuProperty $root, Deposit $deposit) {
        foreach ($deposit->getProperties() as $name) {
            $value = $deposit->getProperty($name);
            if (is_array($value)) {
                $this->logger->warning("AU {$au->getId()} has unsupported property value list {$name}");
                continue;
            }
            $this->buildProperty($au, "attributes.pkppln.{$name}", $value, $root);
        }
    }

    /**
     * Generate and return all the properties for an AU.
     *
     * Persists, but does not flush, properties to the database. You should
     * use the AuValidator to check that the content makes sense before
     * generating all properties.
     *
     * @param Au $au
     *   Generate properties for this AU.
     * @param mixed $clear
     *   If true, remove any properties the AU already has.
     *
     * @see AuValidator::validate
     */
    public function generateProperties(Au $au, $clear = false) {
        if ($clear) {
            foreach ($au->getAuProperties() as $prop) {
                $au->removeAuProperty($prop);
                $this->em->remove($prop);
            }
        }
        $rootName = str_replace('.', '', uniqid('lockssomatic', true));
        $deposit = $au->getDeposits()->first();
        $root = $this->buildProperty($au, $rootName);

        // Definitional properties must go first.
        $propertyNames = array_merge(
            $au->getPlugin()->getDefinitionalPropertyNames(), $au->getPlugin()->getNonDefinitionalProperties()
        );

        $this->baseProperties($au, $root, $deposit);
        $this->configProperties($propertyNames, $au, $root, $deposit);
        $this->contentProperties($au, $root, $deposit);
    }

}
