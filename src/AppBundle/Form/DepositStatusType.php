<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepositStatusType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('agreement', null, array(
            'label' => 'Agreement',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('queryDate', null, array(
            'label' => 'Query Date',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('status', null, array(
            'label' => 'Status',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('deposit');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\DepositStatus'
        ));
    }

}
