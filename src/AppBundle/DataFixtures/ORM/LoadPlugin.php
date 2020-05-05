<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Plugin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some plugins.
 */
class LoadPlugin extends Fixture implements DependentFixtureInterface {
    /**
     * Load the objects.
     */
    public function load(ObjectManager $em) : void {
        $plugin1 = new Plugin();
        $plugin1->setName('Dummy Plugin');
        $plugin1->setPath('/path/to/plugin1.jar');
        $plugin1->setVersion('31');
        $plugin1->setIdentifier('com.example.text');
        $plugin1->setGenerateManifests(true);
        $plugin1->setGeneratedParams([
            'manifest_url',
        ]);
        $em->persist($plugin1);
        $this->setReference('plugin.1', $plugin1);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            LoadPln::class,
        ];
    }
}
