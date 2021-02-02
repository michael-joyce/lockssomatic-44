<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\PluginProperty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Load some plugin properties.
 */
class PluginPropertyFixtures extends Fixture implements DependentFixtureInterface {
    /**
     * Generate a plugin.
     *
     * @param string $key
     * @param string $value
     * @param PluginProperty $parent
     *
     * @return PluginProperty
     */
    private function generate(ObjectManager $em, $key, $value = null, ?PluginProperty $parent = null) {
        $property = new PluginProperty();
        $property->setPlugin($this->getReference('plugin.1'));
        $property->setParent($parent);
        $property->setPropertyKey($key);
        $property->setPropertyValue($value);
        $em->persist($property);

        return $property;
    }

    /**
     * Load the objects.
     */
    public function load(ObjectManager $em) : void {
        $this->generate($em, 'plugin_identifier', 'ca.example.lockss.plugin');
        $this->generate($em, 'au_name', '"Dummy AU %d", container_number');
        $this->generate($em, 'plugin_version', 301);
        $this->generate($em, 'au_permission_url', [
            '"%s", manifest_url',
            '"%s", permission_url',
        ]);
        $props = $this->generate($em, 'plugin_config_props');
        $cpd1 = $this->generate($em, 'org.lockss.daemon.ConfigParamDescr', null, $props);
        $this->generate($em, 'key', 'base_url', $cpd1);
        $this->generate($em, 'definitional', 'true', $cpd1);

        $cpd2 = $this->generate($em, 'org.lockss.daemon.ConfigParamDescr', null, $props);
        $this->generate($em, 'key', 'manifest_url', $cpd2);
        $this->generate($em, 'definitional', 'true', $cpd2);

        $cpd3 = $this->generate($em, 'org.lockss.daemon.ConfigParamDescr', null, $props);
        $this->generate($em, 'key', 'container_number', $cpd3);
        $this->generate($em, 'definitional', 'true', $cpd3);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            PluginFixtures::class,
        ];
    }
}
