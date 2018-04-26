<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\AuStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some AU status objects.
 */
class LoadAuStatus extends Fixture implements DependentFixtureInterface {
    
    /**
     * Load the objects.
     *
     * @param ObjectManager $em
     *   Doctrine object manager.
     */
    public function load(ObjectManager $em) {
        $status1 = new AuStatus();
        $status1->setErrors([
            'Cannot contact host foo.example.com',
        ]);
        $status1->setStatus([
            'localhost' => [
                'accessType' => 'Subscription',
                'contentSize' => 1234568,
                'journalTitle' => 'Some Deposits from a Journal',
                'repository' => '/cache1/gamma/cache/a/',
            ],
        ]);
        $status1->setAu($this->getReference('au.1'));
        $em->persist($status1);
        $this->setReference('auStatus.1', $status1);
        
        $status2 = new AuStatus();
        $status2->setStatus([
            'localhost' => [
                'accessType' => 'Subscription',
                'contentSize' => 2234568,
                'journalTitle' => 'Some Deposits from a Journal',
                'repository' => '/cache1/gamma/cache/a/',
            ],
        ]);
        $status2->setAu($this->getReference('au.1'));
        $em->persist($status2);
        $this->setReference('auStatus.2', $status2);
        
        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            LoadAu::class,
        ];
    }

}
