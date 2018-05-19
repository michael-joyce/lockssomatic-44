<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Utilities\Namespaces;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use SimpleXMLElement;

/**
 * Deposit builder.
 *
 * This service doesn't also construct the associated content
 * items.
 */
class DepositBuilder {

    /**
     * Database manager.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Construct the builder.
     *
     * @param EntityManagerInterface $em
     *   Dependency injected entity manager.
     */
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    /**
     * Build and persist a deposit from an XML element.
     *
     * The deposit isn't flushed to the database.
     *
     * @param SimpleXMLElement $xml
     *   Parsed xml data containing the deposit.
     * @param ContentProvider $provider
     *   Content provider for the deposit.
     *
     * @return Deposit
     *   The constructed deposit.
     */
    public function fromXml(SimpleXMLElement $xml, ContentProvider $provider) {
        $deposit = new Deposit();
        $id = preg_replace('/^urn:uuid:/', '', $xml->children(Namespaces::getNamespace('atom'))->id[0]);
        $title = $xml->children(Namespaces::getNamespace('atom'))->title[0];

        $deposit->setContentProvider($provider);
        $deposit->setTitle((string) $title);
        $deposit->setUuid($id);
        $elements = $xml->xpath('lom:content');
        if(count($elements) > 1) {
            throw new Exception("Multiple content elements not supported in deposit.");
        }
        $content = $elements[0];
        $deposit->setSize((string) $content->attributes()->size);
        $deposit->setChecksumType((string) $content->attributes()->checksumType);
        $deposit->setChecksumValue((string) $content->attributes()->checksumValue);
        $deposit->setUrl(trim((string) $content));
        $deposit->setProperty('journalTitle', (string) $content->attributes('pkp', true)->journalTitle);
        $deposit->setProperty('publisher', (string) $content->attributes('pkp', true)->publisher);
        $deposit->setTitle((string) $content->attributes('pkp', true)->journalTitle);

        foreach ($content->xpath('lom:property') as $property) {
            $deposit->setProperty((string) $property->attributes()->name, (string) $property->attributes()->value);
        }

        $this->em->persist($deposit);

        return $deposit;
    }

    /**
     * Build a deposit from array data.
     *
     * @param array $data
     *   Data to use to build the deposit.
     * @param ContentProvider $provider
     *   Content provider for the deposit.
     *
     * @return Deposit
     *   The constructed deposit.
     */
    public function fromArray(array $data, ContentProvider $provider) {
        $deposit = new Deposit();
        $deposit->setTitle($data['title']);
        $deposit->setSummary($data['summary']);
        $deposit->setContentProvider($provider);
        $deposit->setDateDeposited();

        if (array_key_exists('uuid', $data) && $data['uuid'] !== null && $data['uuid'] !== '') {
            $deposit->setUuid($data['uuid']);
        } else {
            $deposit->setUuid((string) Uuid::uuid4());
        }

        $deposit->setSize($data['size']);
        $deposit->setChecksumType($data['checksum type']);
        $deposit->setChecksumValue($data['checksum value']);
        $deposit->setUrl($data['url']);

        foreach ($data as $key => $value) {
            $deposit->setProperty($key, $value);
        }

        $this->em->persist($deposit);

        return $deposit;
    }

}
