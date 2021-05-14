<?php
declare(strict_types=1);

namespace App\Form\Cockpit\Post;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'imageFile',
                FileType::class,
                [
                    'mapped' => false,
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'style' => 'display:none;',
                        'class' => 'upload-image'
                    ],
                    'constraints' => [
                        new Image([
                            'maxSize' => '3M'
                        ])
                    ],
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
