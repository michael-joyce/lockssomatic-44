<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Deposit;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Description of LoadBox
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class LoadDeposit extends Fixture implements DependentFixtureInterface {

    public function load(ObjectManager $em) {
        $deposit1 = new Deposit();
        $deposit1->setUuid('3E40ACE2-7F1A-4AD5-8622-416EC740D9A1');
        $deposit1->setTitle('Deposit 1');
        $deposit1->setContentProvider($this->getReference('provider.1'));        
        $deposit1->setDateDeposited(new DateTime());
        $this->setReference('deposit.1', $deposit1);
        $em->persist($deposit1);
        
        $deposit2 = new Deposit();
        $deposit2->setUuid('3E40ACE2-7F2A-4AD5-8622-426EC740D9A2');
        $deposit2->setTitle('Deposit 2');
        $deposit2->setContentProvider($this->getReference('provider.1'));        
        $deposit2->setDateDeposited(new DateTime());
        $this->setReference('deposit.2', $deposit2);
        $em->persist($deposit2);

        $deposit3 = new Deposit();
        $deposit3->setUuid('3E40ACE3-7F3A-4AD5-8633-436EC740D9A3');
        $deposit3->setTitle('Deposit 3');
        $deposit3->setContentProvider($this->getReference('provider.1'));        
        $deposit3->setDateDeposited(new DateTime());
        $this->setReference('deposit.3', $deposit3);
        $em->persist($deposit3);

        $em->flush();
    }

    public function getDependencies() {
        return [
            LoadContentProvider::class,
        ];
    }

}
