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

class PlnType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('name', null, array(
            'label' => 'Name',
            'required' => true,
            'attr' => array(
                'help_block' => 'LOCKSSOMatic use only.',
            ),
        ));
        $builder->add('description', null, array(
            'label' => 'Description',
            'required' => false,
            'attr' => array(
                'help_block' => 'LOCKSSOMatic use only.',
            ),
        ));
        $builder->add('username', null, array(
            'label' => 'Username',
            'required' => false,
            'attr' => array(
                'help_block' => 'Username that LOCKSSOMatic should use to communicate with the boxes.',
            ),
        ));
        $builder->add('password', null, array(
            'label' => 'Password',
            'required' => false,
            'attr' => array(
                'help_block' => 'Password that LOCKSSOMatic should use to communicate with the boxes.',
            ),
        ));
        $builder->add('enableContentUi', ChoiceType::class, array(
            'label' => 'Enable Content Ui',
            'expanded' => true,
            'multiple' => false,
            'choices' => array(
                'Enabled' => true,
                'Disabled' => false,
            ),
            'required' => true,
            'placeholder' => false,
            'attr' => array(
                'help_block' => 'Should LOCKSSOMatic enable the ContentUI on all boxes in the PLN? If you enable this feature, you should also set the org.lockss.proxy.access.ip.include property on the PLN.',
            ),
        ));
        $builder->add('contentPort', null, array(
            'label' => 'Content Port',
            'required' => true,
            'attr' => array(
                'help_block' => 'Port for the ContentUI. Required, even if the ContentUI is not enabled.',
            ),
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Pln'
        ));
    }

}
