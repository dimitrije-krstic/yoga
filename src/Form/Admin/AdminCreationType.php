<?php
declare(strict_types=1);

namespace App\Form\Admin;

use App\Form\Constraints\EmailNotExist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminCreationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'Full name',
                        'class' => 'form-control',
                    ],
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            [
                                'min' => 2,
                                'minMessage' => 'Your name is too short',
                            ]
                        ),
                    ],
                ]
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'Email',
                        'class' => 'form-control',
                    ],
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Email(),
                        new EmailNotExist(),
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
