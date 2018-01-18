<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\AuStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Description of LoadBox
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class LoadAuStatus extends Fixture implements DependentFixtureInterface {
    
    public function load(ObjectManager $em) {
        $status1 = new AuStatus();
        $status1->setErrors([
            'Cannot contact host foo.example.com',
        ]);
        $status1->setQueryDate(new \DateTime('2017-01-01'));
        $status1->setStatus([
            'localhost' => [
                'accessType' => 'Subscription',
                'contentSize' => 1234568,
                'journalTitle' => 'Some Deposits from a Journal',
                'repository' => '/cache1/gamma/cache/a/',
            ]
        ]);
        $status1->setAu($this->getReference('au.1'));
        $em->persist($status1);        
        $this->setReference('auStatus.1', $status1);
        
        $status2 = new AuStatus();
        $status2->setErrors([
            'Cannot contact host foo.example.com',
        ]);
        $status2->setQueryDate(new \DateTime('2017-01-02'));
        $status2->setStatus([
            'localhost' => [
                'accessType' => 'Subscription',
                'contentSize' => 2234568,
                'journalTitle' => 'Some Deposits from a Journal',
                'repository' => '/cache1/gamma/cache/a/',
            ]
        ]);
        $status2->setAu($this->getReference('au.1'));
        $em->persist($status2);        
        $this->setReference('auStatus.2', $status2);
        
        $em->flush();
    }

    public function getDependencies() {
        return [
            LoadAu::class,
        ];
    }

}
