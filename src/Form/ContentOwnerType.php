<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Data entry form for content owners.
 */
class ContentOwnerType extends AbstractType
{
    /**
     * Build the form by adding types to $builder.
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void {
        $builder->add('name', null, [
            'label' => 'Name',
            'required' => true,
            'attr' => [
                'help_block' => 'Name of the content owner.',
            ],
        ]);
        $builder->add('emailAddress', null, [
            'label' => 'Email Address',
            'required' => false,
            'attr' => [
                'help_block' => 'Email address to contact the owner.',
            ],
        ]);
    }

    /**
     * Configure default options.
     */
    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\ContentOwner',
        ]);
    }
}
