<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
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
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void {
        $builder->add('hostname', null, [
            'label' => 'Hostname',
            'required' => true,
            'attr' => [
                'help_block' => 'DNS host name.',
            ],
        ]);
        $builder->add('ipAddress', null, [
            'label' => 'Ip Address',
            'required' => false,
            'attr' => [
                'help_block' => 'LOCKSSOMatic will look up the IP address if it is blank.',
            ],
        ]);
        $builder->add('protocol', null, [
            'label' => 'Protocol',
            'required' => true,
            'attr' => [
                'help_block' => 'LOCKSS internal communication protocol. Almost certainly "TCP".',
            ],
        ]);
        $builder->add('port', null, [
            'label' => 'LOCKSS Port',
            'required' => true,
            'attr' => [
                'help_block' => 'This is the port number that LOCKSS uses for internal communication, usually 9729.',
            ],
        ]);
        $builder->add('webServicePort', null, [
            'label' => 'SOAP Port',
            'required' => true,
            'attr' => [
                'help_block' => 'This is the web front end and SOAP Port, usually 8081.',
            ],
        ]);
        $builder->add('webServiceProtocol', ChoiceType::class, [
            'label' => 'Web Service Protocol',
            'required' => true,
            'attr' => [
                'help_block' => '',
            ],
            'choices' => [
                'http' => 'http',
                'https' => 'https',
            ],
            'expanded' => true,
            'multiple' => false,
        ]);
        $builder->add('contactName', null, [
            'label' => 'Contact Name',
            'required' => false,
            'attr' => [
                'help_block' => 'Name of the box admin.',
            ],
        ]);
        $builder->add('contactEmail', null, [
            'label' => 'Contact Email',
            'required' => false,
            'attr' => [
                'help_block' => 'Email address for the box admin.',
            ],
        ]);
        $builder->add('sendNotifications', ChoiceType::class, [
            'label' => 'Send Notifications',
            'expanded' => true,
            'multiple' => false,
            'choices' => [
                'Yes' => true,
                'No' => false,
            ],
            'required' => true,
            'placeholder' => false,
            'attr' => [
                'help_block' => 'Should LOCKSSOMatic send notifications to the box admin if the box is unreachable?',
            ],
        ]);
        $builder->add('active', ChoiceType::class, [
            'label' => 'Active',
            'expanded' => true,
            'multiple' => false,
            'choices' => [
                'Yes' => true,
                'No' => false,
            ],
            'required' => true,
            'placeholder' => false,
            'attr' => [
                'help_block' => 'Should LOCKSSOMatic attempt to contact the box?',
            ],
        ]);
        $builder->add('pln', null, [
            'disabled' => true,
        ]);
    }

    /**
     * Configure default options.
     */
    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Box',
        ]);
    }
}
