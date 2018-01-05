<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BoxType extends AbstractType {

    /**
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
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Box'
        ));
    }

}
