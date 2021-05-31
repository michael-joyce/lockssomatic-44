<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\Deposit;
use App\Entity\DepositStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Load some deposit status entities.
 */
class DepositStatusFixtures extends Fixture implements DependentFixtureInterface {
    /**
     * Load the objects.
     */
    public function load(ObjectManager $em) : void {
        $status1 = new DepositStatus();
        $status1->setAgreement(0.5);
        $status1->setDeposit($this->getReference('deposit.1'));
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
        $status2->setStatus([
            'expected' => 'AC16F7E9C409568E6C7F7B51D1DE18D33E9AF690',
            'localhost' => 'AC16F7E9C409568E6C7F7B51D1DE18D33E9AF690',
            'otherhost' => 'AC16F7E9C409568E6C7F7B51D1DE18D33E9AF690',
        ]);
        $this->setReference('deposit.status.1', $status2);
        $em->persist($status2);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            DepositFixtures::class,
        ];
    }
}
