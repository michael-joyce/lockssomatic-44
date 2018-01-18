<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\DepositStatus;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Description of LoadBox
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class LoadDepositStatus extends Fixture implements DependentFixtureInterface {

    public function load(ObjectManager $em) {
        $status1 = new DepositStatus();
        $status1->setAgreement(0.25);
        $status1->setDeposit($this->getReference('deposit.1'));
        $status1->setQueryDate(new \DateTime('2018-01-01'));
        $status1->setStatus([
            'localhost' => '0.20',
            'otherhost' => '0.30',
        ]);        
        $this->setReference('deposit.status.1', $status1);
        $em->persist($status1);
        
        $status2 = new DepositStatus();
        $status2->setAgreement(0.45);
        $status2->setDeposit($this->getReference('deposit.1'));
        $status2->setQueryDate(new \DateTime('2018-01-01'));
        $status2->setStatus([
            'localhost' => '0.5',
            'otherhost' => '0.4',
        ]);        
        $this->setReference('deposit.status.1', $status2);
        $em->persist($status2);
        
        $status3 = new DepositStatus();
        $status3->setAgreement(1.0);
        $status3->setDeposit($this->getReference('deposit.1'));
        $status3->setQueryDate(new \DateTime('2018-01-01'));
        $status3->setStatus([
            'localhost' => '1.0',
            'otherhost' => '1.0',
        ]);        
        $this->setReference('deposit.status.1', $status3);
        $em->persist($status3);
        
        $em->flush();
    }

    public function getDependencies() {
        return [
            LoadDeposit::class,
        ];
    }

}
