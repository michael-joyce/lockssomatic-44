<?php

namespace AppBundle\Form;

use AppBundle\Services\FileUploader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileUploadType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $maxUpload = min([ini_get('post_max_size'), ini_get('upload_max_filesize')]);
        $builder->add('file', FileType::class, array(
            'label' => $options['label'],
            'required' => true,
            'attr' => array(
                'help_block' => $options['help'],
                'max_size' => ini_get('upload_max_filesize'),
            ),
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => null,
            'label' => '',
            'help' => '',
        ));
    }

}
