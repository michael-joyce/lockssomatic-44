<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\Au;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Load some archival units.
 */
class AuFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Load the objects.
     */
    public function load(ObjectManager $em) : void {
        $au1 = new Au();
        $au1->setAuid('p~a');
        $au1->setContentProvider($this->getReference('provider.1'));
        $au1->setPln($this->getReference('pln.1'));
        $au1->setPlugin($this->getReference('plugin.1'));
        $em->persist($au1);
        $this->setReference('au.1', $au1);

        $au2 = new Au();
        $au2->setAuid('p~b');
        $au2->setContentProvider($this->getReference('provider.1'));
        $au2->setPln($this->getReference('pln.1'));
        $au2->setPlugin($this->getReference('plugin.1'));
        $em->persist($au2);
        $this->setReference('au.2', $au2);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            ContentProviderFixtures::class,
            PluginFixtures::class,
            PlnFixtures::class,
        ];
    }
}
