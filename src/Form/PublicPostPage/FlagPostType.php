<?php
declare(strict_types=1);

namespace App\Form\PublicPostPage;

use App\Entity\FlaggedPost;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class FlagPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'reason',
                TextType::class,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Reason...',
                        'class' => 'form-control',
                    ],
                    'empty_data' => '',
                    'constraints' => [
                        new NotBlank(['message' => 'Reason cannot be empty']),
                    ],
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FlaggedPost::class,
        ]);
    }
}
