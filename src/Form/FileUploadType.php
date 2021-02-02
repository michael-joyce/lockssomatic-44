<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Data entry form for file uploads.
 */
class FileUploadType extends AbstractType {
    /**
     * Build the form by adding types to $builder.
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void {
        $builder->add('file', FileType::class, [
            'label' => $options['label'],
            'required' => true,
            'attr' => [
                'help_block' => $options['help'],
                'max_size' => ini_get('upload_max_filesize'),
            ],
        ]);
    }

    /**
     * Configure default options.
     */
    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'data_class' => null,
            'label' => '',
            'help' => '',
        ]);
    }
}
