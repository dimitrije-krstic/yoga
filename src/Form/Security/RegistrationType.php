<?php
declare(strict_types=1);

namespace App\Form\Security;

use App\Form\Constraints\EmailNotBlocked;
use App\Form\Constraints\EmailNotExist;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
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
                        'placeholder' => 'Your name',
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
                        new EmailNotBlocked()
                    ],
                ]
            )
            ->add(
                'password',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'required' => true,
                    'first_options' => [
                        'label' => false,
                        'attr' => [
                            'placeholder' => 'Password',
                            'class' => 'form-control'
                        ]
                    ],
                    'second_options' => [
                        'label' => false,
                        'attr' => [
                            'placeholder' => 'Retype password',
                            'class' => 'form-control'
                        ]
                    ],
                    'invalid_message' => 'Passwords do not match',
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            [
                                'min' => 8,
                                'max' => 4096,
                                'minMessage' => 'Password should be at least 8 characters long',
                            ]
                        ),
                    ],
                ]
            )
            ->add(
                'captchaCode',
                CaptchaType::class,
                [
                    'required' => false,
                    'invalid_message' => 'Captcha code not correct',
                    'attr' => [
                        'placeholder' => 'Captcha',
                        'class' => 'form-control',
                        'style' => 'margin-top:10px; margin-bottom:10px'
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
