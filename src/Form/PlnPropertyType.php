<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Data entry form for individual pln properties.
 */
class PlnPropertyType extends AbstractType {
    /**
     * Build the form by adding types to $builder.
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void {
        $values = is_array($options['values']) ? $options['values'] : [$options['values']];
        $builder->add('name', TextType::class, [
            'label' => 'Name',
            'required' => true,
            'data' => $options['name'],
        ]);
        $builder->add('values', CollectionType::class, [
            'label' => 'Values',
            'required' => true,
            'data' => $values,
            'allow_add' => true,
            'allow_delete' => true,
            'delete_empty' => true,
            'entry_type' => TextType::class,
            'entry_options' => [
                'label' => false,
            ],
            'by_reference' => false,
            'attr' => [
                'class' => 'collection collection-simple',
            ],
        ]);
    }

    /**
     * Configure default options.
     */
    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'name' => '',
            'values' => [''],
        ]);
    }
}
