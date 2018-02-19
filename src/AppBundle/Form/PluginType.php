<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PluginType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('generateManifests', ChoiceType::class, array(
            'label' => 'Generate Manifests',
            'expanded' => true,
            'multiple' => false,
            'choices' => array(
                'Enabled' => true,
                'Disabled' => false,
            ),
            'required' => true,
            'placeholder' => false,
            'attr' => array(
                'help_block' => 'Should LOCKSSOMatic generate manifest files and set the manifest_url property on AUs?',
            ),
        ));
        
        $plugin = $options['plugin'];
        $names = array();
        foreach($plugin->getConfigPropertyNames() as $name) {
            $names[$name] = $name;
        }
        $builder->add('generatedParams', ChoiceType::class, array(
            'label' => 'Generated Params', 
            'expanded' => true,
            'multiple' => true,
            'choices' => $names,
            'required' => false,
            'attr' => array(
                'help_block' => 'Parameters generated by LOCKSSOMatic will be ignored for matching content to AUs.'
            ),
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Plugin'
        ));
        $resolver->setRequired(array(
            'plugin',
        ));
    }

}
