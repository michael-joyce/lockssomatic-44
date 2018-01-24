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
 * Description of DepositBuilder
 */
class DepositBuilder {
    
    /**
     * @var EntityManagerInterface
     */
    private $em;
    
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }
    
    /**
     * Build and persist a deposit from an XML element.
     * 
     * The deposit isn't flushed to the database.
     *
     * @param SimpleXMLElement $xml
     *
     * @return Deposit
     */
    public function fromXml(SimpleXMLElement $xml, ContentProvider $provider) {
        $deposit = new Deposit();
        $id = preg_replace('/^urn:uuid:/', '', $xml->children(Namespaces::getNamespace('atom'))->id[0]);
        $title = $xml->children(Namespaces::getNamespace('atom'))->title[0];

        $deposit->setContentProvider($provider);
        $deposit->setTitle((string) $title);
        $deposit->setUuid($id);
        $deposit->setDateDeposited();
        
        $this->em->persist($deposit);

        return $deposit;
    }

    /**
     * Build a deposit from array data.
     *
     * @param array $data
     * @param ContentProvider $provider
     *
     * @return Deposit
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
            $deposit->setUuid((string)Uuid::uuid4());
        }
        $this->em->persist($deposit);

        return $deposit;
    }
}
