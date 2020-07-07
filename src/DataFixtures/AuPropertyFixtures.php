<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\AuProperty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Load some AU properties.
 *
 * They're totally unrealistic.
 */
class AuPropertyFixtures extends Fixture implements DependentFixtureInterface {
    /**
     * Load the objects.
     */
    public function load(ObjectManager $em) : void {
        $property1 = new AuProperty();
        $property1->setPropertyKey('test_1');
        $property1->setPropertyValue('Test Property');
        $property1->setAu($this->getReference('au.1'));
        $em->persist($property1);

        $property2 = new AuProperty();
        $property2->setPropertyKey('test_property');
        $property2->setPropertyValue('Test Property Again!?');
        $property2->setAu($this->getReference('au.1'));
        $em->persist($property2);

        $property3 = new AuProperty();
        $property3->setPropertyKey('test_property_other');
        $property3->setPropertyValue('mares eat oats');
        $property3->setAu($this->getReference('au.1'));
        $em->persist($property3);

        $property4 = new AuProperty();
        $property4->setPropertyKey('test_parent');
        $property4->setAu($this->getReference('au.1'));
        $em->persist($property4);

        $property4a = new AuProperty();
        $property4a->setPropertyKey('test_child_1');
        $property4a->setPropertyValue('Bobby Tables');
        $property4a->setAu($this->getReference('au.1'));
        $property4a->setParent($property4);
        $em->persist($property4a);

        $property4b = new AuProperty();
        $property4b->setPropertyKey('test_child_2');
        $property4b->setPropertyValue('mary and jane');
        $property4b->setAu($this->getReference('au.1'));
        $property4b->setParent($property4);
        $em->persist($property4b);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            AuFixtures::class,
        ];
    }
}
