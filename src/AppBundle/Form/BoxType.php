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
                'help_block' => 'DNS host name.',
            ),
        ));
        $builder->add('ipAddress', null, array(
            'label' => 'Ip Address',
            'required' => false,
            'attr' => array(
                'help_block' => 'LOCKSSOMatic will look up the IP address if it is blank.',
            ),
        ));
        $builder->add('protocol', null, array(
            'label' => 'Protocol',
            'required' => true,
            'attr' => array(
                'help_block' => 'LOCKSS internal communication protocol. Almost certainly "TCP".',
            ),
        ));
        $builder->add('port', null, array(
            'label' => 'LOCKSS Port',
            'required' => true,
            'attr' => array(
                'help_block' => 'This is the port number that LOCKSS uses for internal communication, usually 9729.',
            ),
        ));
        $builder->add('webServicePort', null, array(
            'label' => 'SOAP Port',
            'required' => true,
            'attr' => array(
                'help_block' => 'This is the web front end and SOAP Port, usually 8081.',
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
        $builder->add('contactName', null, array(
            'label' => 'Contact Name',
            'required' => false,
            'attr' => array(
                'help_block' => 'Name of the box admin.',
            ),
        ));
        $builder->add('contactEmail', null, array(
            'label' => 'Contact Email',
            'required' => false,
            'attr' => array(
                'help_block' => 'Email address for the box admin.',
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
                'help_block' => 'Should LOCKSSOMatic send notifications to the box admin if the box is unreachable?',
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
                'help_block' => 'Should LOCKSSOMatic attempt to contact the box?',
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
