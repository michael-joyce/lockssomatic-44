<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\CacheStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some cache statuses.
 */
class LoadCacheStatus extends Fixture implements DependentFixtureInterface {
    
    /**
     * Load the objects.
     *
     * @param ObjectManager $em
     *   Doctrine object manager.
     */
    public function load(ObjectManager $em) {
        $status1 = new CacheStatus();
        $status1->setResponse([
            'percentageFull' => 0.02,
            'repositoryId' => 'identifier_1',
            'other' => 'foo',
        ]);
        $status1->setBoxStatus($this->getReference('box.status.1'));
        $em->persist($status1);
        $this->setReference('cache.status.1', $status1);
        
        $status2 = new CacheStatus();
        $status2->setResponse([
            'percentageFull' => 0.03,
            'repositoryId' => 'identifier_2',
            'colours' => 'green,blue,red',
        ]);
        $status2->setBoxStatus($this->getReference('box.status.1'));
        $em->persist($status2);
        $this->setReference('cache.status.2', $status2);
        
        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            LoadBoxStatus::class,
        ];
    }

}
