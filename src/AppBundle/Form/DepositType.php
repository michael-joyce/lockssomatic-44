<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepositType extends AbstractType {

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
        $builder->add('dateDeposited', null, array(
            'label' => 'Date Deposited',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('contentProvider');
        $builder->add('user');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Deposit'
        ));
    }

}
