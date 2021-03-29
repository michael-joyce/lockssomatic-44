<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Form;

use App\Entity\Plugin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Data entry form for content providers.
 */
class ContentProviderType extends AbstractType
{
    /**
     * Build the form by adding types to $builder.
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void {
        $builder->add('uuid', null, [
            'label' => 'Uuid',
            'required' => true,
            'attr' => [
                'help_block' => 'Leave UUID blank to have one generated.',
            ],
        ]);
        $builder->add('permissionurl', UrlType::class, [
            'label' => 'Permission Url',
            'required' => true,
            'attr' => [
                'help_block' => 'URL for the LOCKSS permission statement.',
            ],
        ]);
        $builder->add('name', null, [
            'label' => 'Name',
            'required' => true,
            'attr' => [
                'help_block' => 'Name of the content provider.',
            ],
        ]);
        $builder->add('maxFileSize', null, [
            'label' => 'Max File Size',
            'required' => true,
            'attr' => [
                'help_block' => 'Mazimum file size allowed, in kb (1,000 bytes).',
            ],
        ]);
        $builder->add('maxAuSize', null, [
            'label' => 'Max Au Size',
            'required' => true,
            'attr' => [
                'help_block' => 'Mazimum AU size allowed, in kb (1,000 bytes).',
            ],
        ]);
        $builder->add('contentOwner', null, [
            'required' => true,
        ]);
        $builder->add('pln', null, [
            'required' => true,
        ]);
        $builder->add('plugin', null, [
            'required' => true,
            'choice_label' => function (Plugin $plugin) {
                return $plugin->getName() . ' version ' . $plugin->getVersion();
            },
        ]);
    }

    /**
     * Configure default options.
     */
    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\ContentProvider',
        ]);
    }
}
