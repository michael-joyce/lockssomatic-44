<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\ContentOwner;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Load some content owners.
 */
class ContentOwnerFixtures extends Fixture {
    /**
     * Load the objects.
     */
    public function load(ObjectManager $em) : void {
        $owner1 = new ContentOwner();
        $owner1->setName('Owner 1');
        $em->persist($owner1);
        $this->setReference('owner.1', $owner1);

        $owner2 = new ContentOwner();
        $owner2->setName('Owner 2');
        $em->persist($owner2);
        $this->setReference('owner.2', $owner2);

        $em->flush();
    }
}
