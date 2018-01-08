<?php

namespace AppBundle\Form;

use AppBundle\Services\FileUploader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PluginType extends AbstractType {

    private $fileUploader;
    
    public function __construct(FileUploader $fileUploader) {
        $this->fileUploader = $fileUploader;
    }
    
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('jarFile', FileType::class, array(
            'label' => 'JAR File',
            'required' => true,
            'attr' => array(
                'help_block' => 'Select a LOCKSS plugin JAR file to upload.',
                'data-maxsize' => $this->fileUploader->getMaxUploadSize(),
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
