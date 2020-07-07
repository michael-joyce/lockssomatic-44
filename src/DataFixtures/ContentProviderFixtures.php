<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\ContentProvider;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Load some content providers.
 */
class ContentProviderFixtures extends Fixture implements DependentFixtureInterface {
    public const PROVIDER_UUID_1 = '29125DE2-E622-416C-93EB-E887B2A3126C';
    public const PROVIDER_UUID_2 = 'E5DDD2AB-C971-4492-AD7C-7182111BD367';

    /**
     * Load the objects.
     */
    public function load(ObjectManager $em) : void {
        $provider1 = new ContentProvider();
        $provider1->setUuid(self::PROVIDER_UUID_1);
        $provider1->setPermissionUrl('http://example.com/path/to/permission');
        $provider1->setName('Test Provider 1');
        $provider1->setMaxFileSize(10000);
        $provider1->setMaxAuSize(1000000);
        $provider1->setPlugin($this->getReference('plugin.1'));
        $provider1->setPln($this->getReference('pln.1'));
        $provider1->setContentOwner($this->getReference('owner.1'));
        $em->persist($provider1);
        $this->setReference('provider.1', $provider1);

        $provider2 = new ContentProvider();
        $provider2->setUuid(self::PROVIDER_UUID_2);
        $provider2->setPermissionUrl('http://example.com/path/to/permission');
        $provider2->setName('Test Provider 2');
        $provider2->setMaxFileSize(20000);
        $provider2->setMaxAuSize(2000000);
        $provider2->setPlugin($this->getReference('plugin.1'));
        $provider2->setPln($this->getReference('pln.1'));
        $provider2->setContentOwner($this->getReference('owner.1'));
        $em->persist($provider2);
        $this->setReference('provider.2', $provider2);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            ContentOwnerFixtures::class,
            PlnFixtures::class,
            PluginFixtures::class,
        ];
    }
}
