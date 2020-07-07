<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Data entry form for java keystore files.
 */
class KeystoreType extends AbstractType {
    /**
     * Build the form by adding types to $builder.
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void {
        $builder->add('path', null, [
            'label' => 'Path',
            'required' => true,
            'attr' => [
                'help_block' => '',
            ],
        ]);
        $builder->add('filename', null, [
            'label' => 'Filename',
            'required' => true,
            'attr' => [
                'help_block' => '',
            ],
        ]);
        $builder->add('pln');
    }

    /**
     * Configure default options.
     */
    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Keystore',
        ]);
    }
}
