<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Data entry form for deposits.
 *
 * @todo this should be unused and removed.
 */
class DepositType extends AbstractType {

    /**
     * Build the form by adding types to $builder.
     *
     * @param FormBuilderInterface $builder
     *   Form builder.
     * @param array $options
     *   Unused form options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('uuid', null, array(
            'label' => 'Uuid',
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
        $builder->add('agreement', null, array(
            'label' => 'Agreement',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('summary', null, array(
            'label' => 'Summary',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('contentProvider', null, array(
            'required' => true,
        ));
        $builder->add('user');
    }

    /**
     * Configure default options.
     *
     * @param OptionsResolver $resolver
     *   Options resolver to pass options back to configure the form.
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Deposit',
        ));
    }

}
