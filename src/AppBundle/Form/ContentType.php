<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('url', null, array(
            'label' => 'Url',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('title', null, array(
            'label' => 'Title',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('size', null, array(
            'label' => 'Size',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('checksumType', null, array(
            'label' => 'Checksum Type',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('checksumValue', null, array(
            'label' => 'Checksum Value',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('deposit', null, array(
            'required' => true,
        ));
        $builder->add('au', null, array(
            'required' => true,
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Content'
        ));
    }

}
