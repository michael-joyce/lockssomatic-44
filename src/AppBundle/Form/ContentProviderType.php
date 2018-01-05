<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentProviderType extends AbstractType {

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
        $builder->add('permissionurl', null, array(
            'label' => 'Permissionurl',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('name', null, array(
            'label' => 'Name',
            'required' => true,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('maxFileSize', null, array(
            'label' => 'Max File Size',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('maxAuSize', null, array(
            'label' => 'Max Au Size',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('contentOwner');
        $builder->add('pln');
        $builder->add('plugin');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\ContentProvider'
        ));
    }

}
