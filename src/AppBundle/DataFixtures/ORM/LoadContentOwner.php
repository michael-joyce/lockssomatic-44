<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\ContentOwner;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some content owners.
 */
class LoadContentOwner extends Fixture {
    
    /**
     * Load the objects.
     *
     * @param ObjectManager $em
     *   Doctrine object manager.
     */
    public function load(ObjectManager $em) {
        $owner1 = new ContentOwner();
        $owner1->setName("Owner 1");
        $em->persist($owner1);
        $this->setReference('owner.1', $owner1);
        
        $owner2 = new ContentOwner();
        $owner2->setName("Owner 2");
        $em->persist($owner2);
        $this->setReference('owner.2', $owner2);
        
        $em->flush();
    }

}
