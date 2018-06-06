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

/**
 * Box data entry form.
 */
class BoxType extends AbstractType {

    /**
     * Build the form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('hostname', null, array(
            'label' => 'Hostname',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('protocol', null, array(
            'label' => 'Protocol',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('port', null, array(
            'label' => 'LOCKSS Port',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('webServicePort', null, array(
            'label' => 'SOAP Port',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('webServiceProtocol', ChoiceType::class, array(
            'label' => 'Web Service Protocol',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
            'choices' => array(
                'http' => 'http',
                'https' => 'https',
            ),
            'expanded' => true,
            'multiple' => false,
        ));
        $builder->add('ipAddress', null, array(
            'label' => 'Ip Address',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('contactName', null, array(
            'label' => 'Contact Name',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('contactEmail', null, array(
            'label' => 'Contact Email',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('sendNotifications', ChoiceType::class, array(
            'label' => 'Send Notifications',
            'expanded' => true,
            'multiple' => false,
            'choices' => array(
                'Yes' => true,
                'No' => false,
            ),
            'required' => true,
            'placeholder' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('active', ChoiceType::class, array(
            'label' => 'Active',
            'expanded' => true,
            'multiple' => false,
            'choices' => array(
                'Yes' => true,
                'No' => false,
            ),
            'required' => true,
            'placeholder' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('pln', null, array(
            'disabled' => true,
        ));
    }

    /**
     * Configure default options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Box',
        ));
    }

}
