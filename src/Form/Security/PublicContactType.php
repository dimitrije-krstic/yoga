<?php
declare(strict_types=1);

namespace App\Form\Security;

use App\Entity\User;
use App\Form\Constraints\EmailNotBlocked;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class PublicContactType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'subject',
                ChoiceType::class,
                [
                    'label' => false,
                    'required' => true,
                    'placeholder' => 'Select Subject',
                    'attr' => [
                        'class' => 'form-control',
                    ],
                    'choices' => [

                        'Collaboration' => 'collaboration',
                        'Business proposal' => 'business',
                        'Join the team (volunteers only)' => 'volunteer',
                        'Improvement suggestion' => 'improvement',
                        'New functionality proposal' => 'new',
                        'Report a bug' => 'bug',
                        'Complaint' => 'complaint',
                        'Other' => 'other'
                    ],
                    'constraints' => [
                        new NotNull(['message' => 'Please select the subject.']),
                    ]
                ]
            )
            ->add(
                'content',
                TextareaType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'Your message to us',
                        'class' => 'form-control',
                        'rows' => 10
                    ],
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
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
                        'style' => 'margin-top:10px; margin-bottom:10px',
                        'autocomplete' => 'off'
                    ],
                ]
            )
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }

    public function onPreSetData(FormEvent $event): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if ($user === null || !$user->isVerified()) {
            $event->getForm()
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
                                    'min' => 1,
                                    'minMessage' => 'Please write your name',
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
                            'placeholder' => 'Your email',
                            'class' => 'form-control',
                        ],
                        'required' => true,
                        'constraints' => [
                            new NotBlank(),
                            new Email(),
                            new EmailNotBlocked()
                        ],
                    ]
                )
            ;
        }
    }
}
