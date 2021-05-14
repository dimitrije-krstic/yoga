<?php
declare(strict_types=1);

namespace App\Form\Cockpit\UserAccount;

use App\Entity\User;
use App\Form\Constraints\EmailNotExist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    private UserPasswordEncoderInterface $passwordEncoder;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->passwordEncoder = $passwordEncoder;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Your Name'
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
                'newEmail',
                EmailType::class,
                [
                    'label' => false,
                    'required' => false,
                    'mapped' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Change Email'
                    ],
                    'constraints' => [
                        new Email(),
                        new EmailNotExist()
                    ],
                ]
            )
            ->add(
                'photo',
                FileType::class,
                [
                    'mapped' => false,
                    'label' => false,
                    'required' => false,
                    'constraints' => [
                        new Image([
                            'maxSize' => '2M'
                        ])
                    ],
                    'attr' => [
                        'style' => 'display:none;',
                        'class' => 'user-image-input'
                    ],
                ]
            )
            ->add(
                'accountPubliclyVisible',
                CheckboxType::class,
                [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'form-check-input',
                    ],
                ]
            )
            ->add(
                'currentLocation',
                TextType::class,
                [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Current Location (e.g. City, State, Country)'
                    ],
                    'constraints' => [
                        new Length(
                            [
                                'min' => 2,
                                'minMessage' => 'Please use at least 2 characters',
                            ]
                        ),
                    ],
                ]
            )
            ->add(
                'timezone',
                TimezoneType::class,
                [
                    'label' => false,
                    'required' => false,
                    'placeholder' => 'Choose time zone',
                    'intl' => true,
                    'attr' => [
                        'class' => 'form-control',
                    ],
                    'preferred_choices' => [
                        'America/Los_Angeles',
                        'America/Denver',
                        'America/Chicago',
                        'America/New_York',
                        'Europe/London',
                        'Europe/Brussels',
                        'Africa/Johannesburg',
                        'Asia/Calcutta',
                        'Australia/Perth',
                        'Australia/Sydney'
                    ],
                ]
            )
            ->add(
                'userInfo',
                UserInfoType::class,
                [
                    'compound' => true
                ]
            )
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmitData']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'allow_extra_fields' => true,
        ]);
    }

    public function onPreSetData(FormEvent $event): void
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $event->getForm()
                ->add(
                    'newPassword',
                    RepeatedType::class,
                    [
                        'type' => PasswordType::class,
                        'required' => false,
                        'mapped' => false,
                        'first_options' => [
                            'label' => false,
                            'attr' => [
                                'placeholder' => 'New password',
                                'class' => 'form-control'
                            ]
                        ],
                        'second_options' => [
                            'label' => false,
                            'attr' => [
                                'placeholder' => 'Retype new password',
                                'class' => 'form-control'
                            ]
                        ],
                        'invalid_message' => 'Passwords do not match',
                        'constraints' => [
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
            ;
        }
    }

    public function onPostSubmitData(FormEvent $event): void
    {
        /** @var User $user */
        $user = $event->getData();
        $form = $event->getForm();

        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')
            && ($newPassword = $form->get('newPassword')->getData())
        ) {
            $user->setPassword(
                $this->passwordEncoder->encodePassword(
                    $user,
                    \trim($newPassword)
                )
            );
        }
    }
}
