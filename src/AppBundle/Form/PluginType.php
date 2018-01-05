<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PluginType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('name', null, array(
            'label' => 'Name',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('path', null, array(
            'label' => 'Path',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('filename', null, array(
            'label' => 'Filename',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('version', null, array(
            'label' => 'Version',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('identifier', null, array(
            'label' => 'Identifier',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Plugin'
        ));
    }

}
