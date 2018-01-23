<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentProviderType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('uuid', null, array(
            'label' => 'Uuid',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('permissionurl', UrlType::class, array(
            'label' => 'Permission Url',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('name', null, array(
            'label' => 'Name',
            'required' => true,
            'attr' => array(
                'help_block' => '',
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
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\ContentProvider'
        ));
    }

}
