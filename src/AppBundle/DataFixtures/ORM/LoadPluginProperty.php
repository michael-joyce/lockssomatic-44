<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\PluginProperty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some plugin properties.
 */
class LoadPluginProperty extends Fixture implements DependentFixtureInterface {
    
    /**
     * Load the objects.
     *
     * @param ObjectManager $em
     *   Doctrine object manager.
     */
    public function load(ObjectManager $em) {
        $property1 = new PluginProperty();
        $property1->setPropertyKey("test_1");
        $property1->setPropertyValue("Test Property");
        $property1->setPlugin($this->getReference('plugin.1'));
        $em->persist($property1);
        
        $property2 = new PluginProperty();
        $property2->setPropertyKey("test_property");
        $property2->setPropertyValue("Test Property Again!?");
        $property2->setPlugin($this->getReference('plugin.1'));
        $em->persist($property2);
        
        $property3 = new PluginProperty();
        $property3->setPropertyKey("test_list");
        $property3->setPropertyValue(['list a', 'list b']);
        $property3->setPlugin($this->getReference('plugin.1'));
        $em->persist($property3);
        
        $property4 = new PluginProperty();
        $property4->setPropertyKey("test_parent");
        $property4->setPlugin($this->getReference('plugin.1'));
        $em->persist($property4);
        
        $property4a = new PluginProperty();
        $property4a->setPropertyKey("test_child_1");
        $property4a->setPropertyValue("Bobby Tables");
        $property4a->setPlugin($this->getReference('plugin.1'));
        $property4a->setParent($property4);
        $em->persist($property4a);
        
        $property4b = new PluginProperty();
        $property4b->setPropertyKey("test_child_2");
        $property4b->setPropertyValue(['Mary', 'Jane']);
        $property4b->setPlugin($this->getReference('plugin.1'));
        $property4b->setParent($property4);
        $em->persist($property4b);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            LoadPlugin::class,
        ];
    }

}
