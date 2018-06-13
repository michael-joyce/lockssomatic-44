<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Form;

use AppBundle\Entity\Plugin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Data entry form for content providers.
 */
class ContentProviderType extends AbstractType {

    /**
     * Build the form by adding types to $builder.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('uuid', null, array(
            'label' => 'Uuid',
            'required' => true,
            'attr' => array(
                'help_block' => 'Leave UUID blank to have one generated.',
            ),
        ));
        $builder->add('permissionurl', UrlType::class, array(
            'label' => 'Permission Url',
            'required' => true,
            'attr' => array(
                'help_block' => 'URL for the LOCKSS permission statement.',
            ),
        ));
        $builder->add('name', null, array(
            'label' => 'Name',
            'required' => true,
            'attr' => array(
                'help_block' => 'Name of the content provider.',
            ),
        ));
        $builder->add('maxFileSize', null, array(
            'label' => 'Max File Size',
            'required' => true,
            'attr' => array(
                'help_block' => 'Mazimum file size allowed, in kb (1,000 bytes).',
            ),
        ));
        $builder->add('maxAuSize', null, array(
            'label' => 'Max Au Size',
            'required' => true,
            'attr' => array(
                'help_block' => 'Mazimum AU size allowed, in kb (1,000 bytes).',
            ),
        ));
        $builder->add('contentOwner', null, array(
            'required' => true,
        ));
        $builder->add('pln', null, array(
            'required' => true,
        ));
        $builder->add('plugin', null, array(
            'required' => true,
            'choice_label' => function (Plugin $plugin) {
                return $plugin->getName() . ' version ' . $plugin->getVersion();
            },
        ));
    }

    /**
     * Configure default options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\ContentProvider',
        ));
    }

}
