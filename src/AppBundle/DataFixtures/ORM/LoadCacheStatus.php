<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\CacheStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Description of LoadBox
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class LoadCacheStatus extends Fixture implements DependentFixtureInterface {
    
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

    public function getDependencies() {
        return [
            LoadBoxStatus::class,
        ];
    }

}
