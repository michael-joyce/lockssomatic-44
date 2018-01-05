<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('url', null, array(
            'label' => 'Url',
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
        $builder->add('size', null, array(
            'label' => 'Size',
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
        $builder->add('checksumType', null, array(
            'label' => 'Checksum Type',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('checksumValue', null, array(
            'label' => 'Checksum Value',
            'required' => false,
            'attr' => array(
                'help_block' => '',
            ),
        ));
        $builder->add('deposit');
        $builder->add('au');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Content'
        ));
    }

}
