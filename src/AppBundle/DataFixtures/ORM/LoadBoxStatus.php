<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\BoxStatus;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Description of LoadBox
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class LoadBoxStatus extends Fixture implements DependentFixtureInterface {
    
    public function load(ObjectManager $em) {
        $status1 = new BoxStatus();
        $status1->setQueryDate(new DateTime('2017-01-01'));
        $status1->setSuccess(false);
        $status1->setErrors('Cannot connect to host.');
        $status1->setBox($this->getReference('box.1'));
        $em->persist($status1);        
        $this->setReference('box.status.1', $status1);
        
        $status2 = new BoxStatus();
        $status2->setQueryDate(new DateTime('2017-01-01'));
        $status2->setSuccess(true);
        $status2->setBox($this->getReference('box.1'));
        $em->persist($status2);        
        $this->setReference('box.status.2', $status2);
        
        $em->flush();
    }

    public function getDependencies() {
        return [
            LoadBox::class,
        ];
    }

}
