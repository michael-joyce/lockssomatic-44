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
        $status1->setAgreement(0.5);
        $status1->setDeposit($this->getReference('deposit.1'));
        $status1->setQueryDate(new \DateTime('2018-01-01'));
        $status1->setStatus([
            'expected' => 'AC16F7E9C409568E6C7F7B51D1DE18D33E9AF690',
            'localhost' => 'AC16F7E9C409568E6C7F7B51D1DE18D33E9AF690',
            'otherhost' => '*',
        ]);        
        $this->setReference('deposit.status.1', $status1);
        $em->persist($status1);
        
        $status2 = new DepositStatus();
        $status2->setAgreement(1.0);
        $status2->setDeposit($this->getReference('deposit.1'));
        $status2->setQueryDate(new \DateTime('2018-01-02'));
        $status2->setStatus([
            'expected' => 'AC16F7E9C409568E6C7F7B51D1DE18D33E9AF690',
            'localhost' => 'AC16F7E9C409568E6C7F7B51D1DE18D33E9AF690',
            'otherhost' => 'AC16F7E9C409568E6C7F7B51D1DE18D33E9AF690',
        ]);        
        $this->setReference('deposit.status.1', $status2);
        $em->persist($status2);
                
        $em->flush();
    }

    public function getDependencies() {
        return [
            LoadDeposit::class,
        ];
    }

}
