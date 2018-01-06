<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\ContentOwner;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Description of LoadBox
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class LoadContentOwner extends Fixture {
    
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
