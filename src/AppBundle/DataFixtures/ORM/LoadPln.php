<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Pln;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some PLNs.
 */
class LoadPln extends Fixture {
    
    /**
     * Load the objects.
     *
     * @param ObjectManager $em
     *   Doctrine object manager.
     */
    public function load(ObjectManager $em) {
        $pln1 = new Pln();
        $pln1->setName('Network Test');
        $pln1->setEnableContentUi(false);
        $em->persist($pln1);
        $this->setReference('pln.1', $pln1);
        
        $pln2 = new Pln();
        $pln2->setName('Testing Network');
        $pln2->setEnableContentUi(false);
        $em->persist($pln2);
        $this->setReference('pln.2', $pln2);
        
        $em->flush();
    }
    
}
