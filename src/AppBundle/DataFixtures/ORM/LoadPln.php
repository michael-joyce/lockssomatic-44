<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Pln;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Description of LoadPln
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class LoadPln extends Fixture {
    
    public function load(ObjectManager $em) {
        $pln1 = new Pln();
        $pln1->setName('pln1');
        $pln1->setEnableContentUi(false);
        $em->persist($pln1);
        $this->setReference('pln.1', $pln1);
        
        $pln2 = new Pln();
        $pln2->setName('pln2');
        $pln2->setEnableContentUi(false);
        $em->persist($pln2);
        $this->setReference('pln.2', $pln2);
        
        $em->flush();
        
    }
    
}
