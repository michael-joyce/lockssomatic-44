<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Au;
use App\Entity\AuProperty;
use App\Entity\Deposit;
use App\Repository\AuRepository;
use App\Utilities\Encoder;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Generator;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Manage all AUs and queries on them.
 */
class AuManager
{
    use LoggerAwareTrait;

    /**
     * Batch size for iterating deposits in AUs.
     */
    public const BATCHSIZE = 25;

    /**
     * Database mapper.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * AU repository for database queries.
     *
     * @var AuRepository
     */
    private $auRepository;

    /**
     * URL generator.
     *
     * @var RouterInterface
     */
    private $router;

    /**
     * Build the manager.
     */
    public function __construct(EntityManagerInterface $em, RouterInterface $router) {
        $this->em = $em;
        $this->router = $router;
        $this->auRepository = $em->getRepository(Au::class);
    }

    /**
     * Set or override the AU repository.
     */
    public function setAuRepository(AuRepository $repo) : void {
        $this->auRepository = $repo;
    }

    /**
     * Calculate the size of an AU.
     *
     * @return int
     */
    public function auSize(Au $au) {
        return $this->auRepository->getAuSize($au);
    }

    /**
     * Count the deposits in an AU.
     *
     * @return int
     */
    public function countDeposits(Au $au) {
        return $this->auRepository->countDeposits($au);
    }

    /**
     * Get an iterator over the deposits in the AU.
     *
     * @return Deposit[]|Generator
     *                             The resulting generator.
     */
    public function auDeposits(Au $au) {
        return $this->auRepository->iterateDeposits($au);
    }

    /**
     * Build one AU from a deposit and persist it.
     *
     * Does not trigger a database flush.
     *
     * @param string $auid
     *
     * @return Au
     */
    public function buildAu(Deposit $deposit, $auid) {
        $provider = $deposit->getContentProvider();
        $au = new Au();
        $au->setContentProvider($provider);
        $au->setPln($provider->getPln());
        $au->setAuid($auid);
        $au->setPlugin($provider->getPlugin());
        $this->em->persist($au);

        return $au;
    }

    /**
     * Find an open AU for a deposit.
     *
     * If an open Au cannot be found, one will be created. persists the new AU,
     * but does not flush it to the database. May close an existing AU.
     *
     * @return Au
     */
    public function findOpenAu(Deposit $deposit) {
        $provider = $deposit->getContentProvider();
        $auid = $this->generateAuidFromDeposit($deposit, false);
        $au = $this->auRepository->findOpenAu($auid);
        if ($au && $this->auSize($au) + $deposit->getSize() > $provider->getMaxAuSize()) {
            $au->setOpen(false);
            $au = null;
        }
        if ( ! $au) {
            $au = $this->buildAu($deposit, $auid);
        }
        $au->addDeposit($deposit);
        $deposit->setAu($au);

        return $au;
    }

    /**
     * Validate the AU content.
     *
     * Checks that every deposit in the AU has the same set
     * of definitional configuration parameters, and returns the
     * number of errors.
     *
     * @throws Exception
     *                   If the AU is empty or is missing a plugin.
     *
     * @return int
     */
    public function validate(Au $au) {
        $errors = 0;
        $plugin = $au->getPlugin();
        if ( ! $plugin) {
            throw new Exception('Cannot validate an AU without a plugin.');
        }
        $definitional = $plugin->getDefinitionalPropertyNames();
        if ( ! $definitional || 0 === count($definitional)) {
            throw new Exception('Cannot validate AU for plugin without definitional properties.');
        }

        if (0 === $this->countDeposits($au)) {
            if ($this->em->contains($au)) {
                $this->logger->warning("AU {$au->getId()} has no deposits and cannot be validated.");
            } else {
                $this->logger->warning('Non-persisted AU has no deposits and cannot be validated.');
            }

            return 0;
        }

        $iterator = $this->auDeposits($au);
        $first = $iterator->current()[0];

        $base = [];

        foreach ($definitional as $name) {
            $base[$name] = $first->getProperty($name);
        }

        $i = 0;
        while ($iterator->valid()) {
            $deposit = $iterator->current()[0];

            foreach ($definitional as $name) {
                if ($deposit->getProperty($name) !== $base[$name]) {
                    $errors++;
                }
            }
            $iterator->next();

            $i++;
            if (0 === $i % self::BATCHSIZE) {
                $this->em->clear();
            }
        }

        return $errors;
    }

    /**
     * Build a property.
     *
     * The property is persisted, but not flushed, to the database.
     *
     * @param string $key
     * @param string $value
     * @param AuProperty $parent
     *
     * @return AuProperty
     */
    public function buildProperty(Au $au, $key, $value = null, ?AuProperty $parent = null) {
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
     * @param string $value
     *
     * @throws Exception
     *
     * @return string
     */
    public function generateString(Au $au, $value) {
        $matches = [];
        $formatStr = '';
        if (preg_match('/^"([^"]*)"/', $value, $matches)) {
            $formatStr = $matches[1];
        } else {
            throw new Exception("Property cannot be parsed: {$value}");
        }
        $parameterString = mb_substr($value, mb_strlen($formatStr) + 2);
        // substr/strlen skips the $formatstr part of the property.
        $parameters = preg_split('/, */', $parameterString);
        $values = [];

        foreach (array_slice($parameters, 1) as $parameterName) {
            $values[] = $au->getAuPropertyValue($parameterName);
        }
        $paramCount = preg_match_all('/%[a-zA-Z]/', $formatStr);
        if ($paramCount !== count($values)) {
            throw new Exception("Wrong number of parameters for format string: {$formatStr}/{$paramCount}");
        }

        return vsprintf($formatStr, $values);
    }

    /**
     * Generate a symbol, according to a LOCKSS vstring-like property.
     *
     * LOCKSS plugin configuration symbols can be strings or lists. Ugh.
     *
     * @param string $name
     *
     * @throws Exception
     *
     * @return array|string
     *                      The symbol as a string or a list of strings.
     */
    public function generateSymbol(Au $au, $name) {
        $plugin = $au->getPlugin();
        if ( ! $plugin) {
            throw new Exception("Au requires plugin to generate {$name}.");
        }
        $property = $plugin->getProperty($name);
        if (null === $property) {
            throw new Exception("{$plugin->getName()} is missing parameter {$name}.");
        }
        if ( ! $property->isList()) {
            return $this->generateString($au, $property->getPropertyValue());
        }
        $values = [];

        foreach ($property->getPropertyValue() as $v) {
            $values[] = $this->generateString($au, $v);
        }

        return $values;
    }

    /**
     * Generate the base properties, required for any AU.
     */
    public function baseProperties(Au $au, AuProperty $root, Deposit $deposit) : void {
        $this->buildProperty($au, 'journalTitle', $deposit->getProperty('journalTitle'), $root);
        if ($this->em->contains($au)) {
            $this->buildProperty($au, 'title', 'LOCKSSOMatic AU ' . $au->getId() . ' ' . $deposit->getTitle(), $root);
        } else {
            $this->buildProperty($au, 'title', 'LOCKSSOMatic AU ' . ' ' . $deposit->getTitle(), $root);
        }
        $this->buildProperty($au, 'plugin', $au->getPlugin()->getIdentifier(), $root);
        $this->buildProperty($au, 'attributes.publisher', $deposit->getProperty('publisher'), $root);
    }

    /**
     * Generate the configuration parameters for an AU.
     */
    public function configProperties(array $propertyNames, Au $au, AuProperty $root, Deposit $deposit) : void {
        $manifestUrl = $this->router->generate('lockss_manifest', [
            'plnId' => $au->getPln()->getId(),
            'ownerId' => $au->getContentProvider()->getContentOwner()->getId(),
            'providerId' => $au->getContentProvider()->getId(),
            'auId' => $au->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        foreach ($propertyNames as $index => $name) {
            switch ($name) {
                case 'manifest_url':
                    $value = $manifestUrl;

                    break;
                case 'permission_url':
                    $value = $au->getContentProvider()->getPermissionUrl();

                    break;
                case 'base_url':
                    $value = $deposit->getProperty('base_url');

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
     */
    public function contentProperties(Au $au, AuProperty $root, Deposit $deposit) : void {
        foreach ($deposit->getProperties() as $name) {
            $value = $deposit->getProperty($name);
            if (is_array($value)) {
                if ($this->em->contains($au)) {
                    $this->logger->warning("AU {$au->getId()} has unsupported property value list {$name}");
                } else {
                    $this->logger->warning("Non-persisted AU has unsupported property value list {$name}");
                }

                continue;
            }
            $this->buildProperty($au, "attributes.pkppln.{$name}", $value, $root);
        }
    }

    /**
     * Generate and return all the properties for an AU.
     *
     * Persists, but does not flush, properties to the database. You should
     * use validate the AU to check that the content makes sense before
     * generating all properties.
     *
     * @param mixed $clear
     *
     * @see validate
     */
    public function generateProperties(Au $au, $clear = false) : void {
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
            $au->getPlugin()->getDefinitionalPropertyNames(),
            $au->getPlugin()->getNonDefinitionalProperties()
        );

        $this->baseProperties($au, $root, $deposit);
        $this->configProperties($propertyNames, $au, $root, $deposit);
        $this->contentProperties($au, $root, $deposit);
    }

    /**
     * Generate an AUID from a deposit.
     *
     * @param bool $lockssAuid
     *
     * @throws Exception
     *                   If the deposit is missing a required property, an exception is thrown.
     *
     * @return string
     */
    public function generateAuidFromDeposit(Deposit $deposit, $lockssAuid = true) {
        $encoder = new Encoder();
        $plugin = $deposit->getPlugin();
        $pluginId = $plugin->getIdentifier();
        $pluginKey = str_replace('.', '|', $pluginId);
        $auKey = '';
        $propNames = $plugin->getDefinitionalPropertyNames();
        sort($propNames);

        foreach ($propNames as $name) {
            if ( ! $lockssAuid && in_array($name, $plugin->getGeneratedParams(), true)) {
                continue;
            }
            $value = null;
            if ($lockssAuid) {
                $value = $encoder->encode($deposit->getAu()->getAuPropertyValue($name));
            } else {
                $value = $encoder->encode($deposit->getProperty($name));
            }
            if ( ! $value) {
                throw new Exception("Cannot generate AUID without definitional property {$name}.");
            }
            $auKey .= "&{$name}~{$value}";
        }

        return $pluginKey . $auKey;
    }

    /**
     * Sets the AU id based on the first content item in the AU and returns it.
     *
     * Assumes that the AU properties are already generated.
     *
     * @param bool $lockssAuid
     *
     * @throws Exception
     *                   If the AU is missing a required property.
     *
     * @return null|string
     *                     The generated AUID.
     */
    public function generateAuidFromAu(Au $au, $lockssAuid = true) {
        if (0 === $au->getDeposits()->count()) {
            return;
        }
        $plugin = $au->getPlugin();
        if (null === $plugin) {
            return;
        }

        return $this->generateAuidFromDeposit($au->getDeposits()->first(), $lockssAuid);
    }
}
