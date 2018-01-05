<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('managed', ChoiceType::class, array(
            'label' => 'Managed',
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
        $builder->add('auid', null, array(
            'label' => 'Auid',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('comment', null, array(
            'label' => 'Comment',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('pln');
        $builder->add('contentProvider');
        $builder->add('plugin');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Au'
        ));
    }

}
