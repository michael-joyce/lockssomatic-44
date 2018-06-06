<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Au;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some archival units.
 */
class LoadAu extends Fixture implements DependentFixtureInterface {

    /**
     * Load the objects.
     *
     * @param ObjectManager $em
     */
    public function load(ObjectManager $em) {
        $au1 = new Au();
        $au1->setAuid('p~a');
        $au1->setContentProvider($this->getReference('provider.1'));
        $au1->setPln($this->getReference('pln.1'));
        $au1->setPlugin($this->getReference('plugin.1'));
        $em->persist($au1);
        $this->setReference('au.1', $au1);

        $au2 = new Au();
        $au2->setAuid('p~b');
        $au2->setContentProvider($this->getReference('provider.1'));
        $au2->setPln($this->getReference('pln.1'));
        $au2->setPlugin($this->getReference('plugin.1'));
        $em->persist($au2);
        $this->setReference('au.2', $au2);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            LoadContentProvider::class,
            LoadPlugin::class,
            LoadPln::class,
        ];
    }

}
