<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\AuStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some AU status objects.
 */
class LoadAuStatus extends Fixture implements DependentFixtureInterface {
    /**
     * Load the objects.
     */
    public function load(ObjectManager $em) : void {
        $status1 = new AuStatus();
        $status1->setErrors([
            'Cannot contact host foo.example.com',
        ]);
        $status1->setStatus([
            'localhost' => [
                'accessType' => 'Subscription',
                'contentSize' => 1234568,
                'journalTitle' => 'Some Deposits from a Journal',
                'repository' => '/cache1/gamma/cache/a/',
            ],
        ]);
        $status1->setAu($this->getReference('au.1'));
        $em->persist($status1);
        $this->setReference('auStatus.1', $status1);

        $status2 = new AuStatus();
        $status2->setStatus([
            'localhost' => [
                'accessType' => 'Subscription',
                'contentSize' => 2234568,
                'journalTitle' => 'Some Deposits from a Journal',
                'repository' => '/cache1/gamma/cache/a/',
            ],
        ]);
        $status2->setAu($this->getReference('au.1'));
        $em->persist($status2);
        $this->setReference('auStatus.2', $status2);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            LoadAu::class,
        ];
    }
}
