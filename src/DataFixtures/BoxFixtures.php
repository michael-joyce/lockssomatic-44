<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\Box;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Load a few boxes.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class BoxFixtures extends Fixture implements DependentFixtureInterface {
    /**
     * Load the objects.
     */
    public function load(ObjectManager $em) : void {
        $box1 = new Box();
        $box1->setHostname('localhost');
        $box1->setIpAddress('127.0.0.1');
        $box1->setProtocol('TCP');
        $box1->setPort('1234');
        $box1->setWebServicePort('11234');
        $box1->setSendNotifications(false);
        $box1->setActive(false);
        $box1->setPln($this->getReference('pln.1'));
        $em->persist($box1);
        $this->setReference('box.1', $box1);

        $box2 = new Box();
        $box2->setHostname('localhost');
        $box2->setIpAddress('127.0.0.1');
        $box2->setProtocol('TCP');
        $box2->setPort('2234');
        $box2->setWebServicePort('22234');
        $box2->setSendNotifications(false);
        $box2->setActive(false);
        $box2->setPln($this->getReference('pln.2'));
        $em->persist($box2);
        $this->setReference('box.2', $box2);
        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            PlnFixtures::class,
        ];
    }
}
