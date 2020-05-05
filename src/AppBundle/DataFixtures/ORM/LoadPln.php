<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
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
     */
    public function load(ObjectManager $em) : void {
        $pln1 = new Pln();
        $pln1->setName('Network Test');
        $pln1->setEnableContentUi(false);
        $pln1->setEmail('pln1@example.com');
        $em->persist($pln1);
        $this->setReference('pln.1', $pln1);

        $pln2 = new Pln();
        $pln2->setName('Testing Network');
        $pln2->setEnableContentUi(false);
        $pln2->setEmail('pln2@example.com');
        $em->persist($pln2);
        $this->setReference('pln.2', $pln2);

        $em->flush();
    }
}
