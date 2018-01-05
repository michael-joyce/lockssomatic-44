<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuPropertyType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('propertyKey', null, array(
            'label' => 'Property Key',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('propertyValue', null, array(
            'label' => 'Property Value',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('parent');
        $builder->add('au');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\AuProperty'
        ));
    }

}
