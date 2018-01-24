<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Plugin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some plugins.
 */
class LoadPlugin extends Fixture implements DependentFixtureInterface {
    
    /**
     * Load the objects.
     *
     * @param ObjectManager $em
     *   Doctrine object manager.
     */
    public function load(ObjectManager $em) {
        $plugin1 = new Plugin();
        $plugin1->setName('Test Plugin 1');
        $plugin1->setPath('/path/to/plugin1.jar');
        $plugin1->setVersion('31');
        $plugin1->setIdentifier('com.example.text');
        $em->persist($plugin1);
        $this->setReference('plugin.1', $plugin1);

        $plugin2 = new Plugin();
        $plugin2->setName('Test Plugin 2');
        $plugin2->setPath('/path/to/plugin2.jar');
        $plugin2->setVersion('12');
        $plugin2->setIdentifier('com.example.text');
        $em->persist($plugin2);
        $this->setReference('plugin.2', $plugin2);
        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            LoadPln::class,
        ];
    }

}
