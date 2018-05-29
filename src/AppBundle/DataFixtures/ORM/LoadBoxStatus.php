<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\BoxStatus;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some box statuses.
 */
class LoadBoxStatus extends Fixture implements DependentFixtureInterface {
    
    /**
     * Load the objects.
     *
     * @param ObjectManager $em
     *   Doctrine object manager.
     */
    public function load(ObjectManager $em) {
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
            LoadBox::class,
        ];
    }

}
