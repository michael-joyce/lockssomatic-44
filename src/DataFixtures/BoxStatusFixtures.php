<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\BoxStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Load some box statuses.
 */
class BoxStatusFixtures extends Fixture implements DependentFixtureInterface {
    /**
     * Load the objects.
     */
    public function load(ObjectManager $em) : void {
        $status1 = new BoxStatus();
        $status1->setSuccess(false);
        $status1->setErrors('Cannot connect to host.');
        $status1->setBox($this->getReference('box.1'));
        $em->persist($status1);
        $this->setReference('box.status.1', $status1);

        $status2 = new BoxStatus();
        $status2->setSuccess(true);
        $status2->setBox($this->getReference('box.1'));
        $em->persist($status2);
        $this->setReference('box.status.2', $status2);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            BoxFixtures::class,
        ];
    }
}
