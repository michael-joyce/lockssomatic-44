<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\PluginProperty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some plugin properties.
 */
class LoadPluginProperty extends Fixture implements DependentFixtureInterface {
        
    /**
     * Load the objects.
     *
     * @param ObjectManager $em
     *   Doctrine object manager.
     */
    public function load(ObjectManager $em) {
        $this->generate($em, 'plugin_identifier', 'ca.example.lockss.plugin');
        $this->generate($em, 'au_name', '"Dummy AU %d", container_number');
        $this->generate($em, 'plugin_version', 301);
        $this->generate($em, 'au_permission_url', array(
            '"%s", manifest_url',
            '"%s", permission_url',
        ));
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
            LoadPlugin::class,
        ];
    }
    
    /**
     *
     */
    private function generate(ObjectManager $em, $key, $value = null, PluginProperty $parent = null) {
        $property = new PluginProperty();
        $property->setPlugin($this->getReference('plugin.1'));
        $property->setParent($parent);
        $property->setPropertyKey($key);
        $property->setPropertyValue($value);
        $em->persist($property);
        return $property;
    }

}
