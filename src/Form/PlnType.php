<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Data entry form for plns.
 */
class PlnType extends AbstractType {
    /**
     * Build the form by adding types to $builder.
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void {
        $builder->add('name', null, [
            'label' => 'Name',
            'required' => true,
            'attr' => [
                'help_block' => 'LOCKSSOMatic use only.',
            ],
        ]);
        $builder->add('description', null, [
            'label' => 'Description',
            'required' => false,
            'attr' => [
                'help_block' => 'LOCKSSOMatic use only.',
            ],
        ]);
        $builder->add('username', null, [
            'label' => 'Username',
            'required' => false,
            'attr' => [
                'help_block' => 'Username that LOCKSSOMatic should use to communicate with the boxes.',
            ],
        ]);
        $builder->add('password', PasswordType::class, [
            'label' => 'Password',
            'required' => false,
            'attr' => [
                'help_block' => 'Password that LOCKSSOMatic should use to communicate with the boxes.',
            ],
        ]);
        $builder->add('email', null, [
            'label' => 'Email',
            'required' => true,
            'attr' => [
                'help_block' => 'Network email address to display in the LOCKSS UI.',
            ],
        ]);
        $builder->add('enableContentUi', ChoiceType::class, [
            'label' => 'Enable Content Ui',
            'expanded' => true,
            'multiple' => false,
            'choices' => [
                'Enabled' => true,
                'Disabled' => false,
            ],
            'required' => true,
            'placeholder' => false,
            'attr' => [
                'help_block' => 'Should LOCKSSOMatic enable the ContentUI on all boxes in the PLN? If you enable this feature, you should also set the org.lockss.proxy.access.ip.include property on the PLN.',
            ],
        ]);
        $builder->add('contentPort', null, [
            'label' => 'Content Port',
            'required' => true,
            'attr' => [
                'help_block' => 'Port for the ContentUI. Required, even if the ContentUI is not enabled.',
            ],
        ]);
    }

    /**
     * Configure default options.
     */
    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Pln',
        ]);
    }
}
