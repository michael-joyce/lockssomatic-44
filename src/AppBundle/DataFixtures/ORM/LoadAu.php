<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Au;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Description of LoadBox
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class LoadAu extends Fixture implements DependentFixtureInterface {
    
    public function load(ObjectManager $em) {
        $au1 = new Au();
        $au1->setAuid("phone-au-id-1");
        $au1->setContentProvider($this->getReference('provider.1'));
        $au1->setPln($this->getReference('pln.1'));
        $au1->setPlugin($this->getReference('plugin.1'));        
        $em->persist($au1);        
        $this->setReference('au.1', $au1);
        
        $au2 = new Au();
        $au2->setAuid("phone-au-id-27b/4");
        $au2->setContentProvider($this->getReference('provider.1'));
        $au2->setPln($this->getReference('pln.1'));
        $au2->setPlugin($this->getReference('plugin.1'));        
        $em->persist($au2);        
        $this->setReference('au.1', $au2);
        
        $em->flush();
    }

    public function getDependencies() {
        return [
            LoadContentProvider::class,
            LoadPlugin::class,
            LoadPln::class,
        ];
    }

}
