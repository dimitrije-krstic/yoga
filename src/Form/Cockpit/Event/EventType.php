<?php
declare(strict_types=1);

namespace App\Form\Cockpit\Event;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Intl\Timezones;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Url;

class EventType extends AbstractType
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
                'title',
                TextType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'Title',
                        'class' => 'form-control',
                        'autocomplete' => 'off'
                    ],
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                    ],
                ]
            )
            ->add(
                'link',
                UrlType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'Link to the event (e.g. zoom-link)',
                        'class' => 'form-control',
                        'autocomplete' => 'off'
                    ],
                    'required' => false,
                    'constraints' => [
                        new Url()
                    ],
                ]
            )
            ->add(
                'linkPassword',
                TextType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'Password to access event (optional)',
                        'class' => 'form-control',
                        'autocomplete' => 'off'
                    ],
                    'required' => false,
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Event description...',
                        'class' => 'form-control',
                        'rows' => 8
                    ],
                ]
            )
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmitData']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'allow_extra_fields' => true,
            'empty_data' => function (FormInterface $form) {
                return new Event($this->security->getUser());
            },
        ]);
    }

    public function onPreSetData(FormEvent $event): void
    {
        /** @var Event|null $onlineEvent */
        $onlineEvent = $event->getData();
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$onlineEvent) {
            $event->getForm()->add(
                'repeat',
                IntegerType::class,
                [
                    'mapped' => false,
                    'label' => false,
                    'required' => false,
                    'data' => 0,
                    'attr' => [
                        'class' => 'form-control',
                    ],
                    'constraints' => [
                        new Range(['min' => 0, 'max' => 8])
                    ],
                ]
            );
        }

        if (!$onlineEvent || $onlineEvent->getPublished() === null) {
            $event->getForm()
                ->add(
                    'category',
                    ChoiceType::class,
                    [
                        'label' => false,
                        'required' => true,
                        'attr' => [
                            'class' => 'form-control'
                        ],
                        'choices' => [
                            'Main focus' => null,
                            'Asanas & Pranayamas' => 1,
                            'Guided Meditation' => 2,
                            'Kirtan & Mantra Chanting' => 3,
                            'Satsang' => 4,
                            'Diverse' => 5
                        ],
                       'constraints' => [
                           new NotNull(['message' => 'You have to select main focus of your event']),
                           new Range(['min' => 1, 'max' => 5])
                        ]
                    ]
                )
                ->add(
                    'date',
                    TextType::class,
                    [
                        'label' => false,
                        'mapped' => false,
                        'required' => true,
                        'attr' => [
                            'class' => 'form-control datetimepicker-input',
                            'type' => 'text',
                            'data-target' => "#datetimepicker1",
                            'data-toggle' => 'datetimepicker',
                            'placeholder' => 'Event Date',
                            'autocomplete' => 'off'
                        ],
                        'constraints' => [
                            new NotBlank(['message' => 'You need to specify a date of your event.']),
                        ],
                    ]
                )
                ->add(
                    'time',
                    TextType::class,
                    [
                        'label' => false,
                        'mapped' => false,
                        'required' => true,
                        'attr' => [
                            'class' => 'form-control datetimepicker-input',
                            'type' => 'text',
                            'data-target' => "#datetimepicker2",
                            'data-toggle' => 'datetimepicker',
                            'placeholder' => 'Starting Time',
                            'autocomplete' => 'off'
                        ],
                        'constraints' => [
                            new NotBlank(['message' => 'You need to specify a starting time of your event.']),
                        ],
                    ]
                )
                ->add(
                    'duration',
                    IntegerType::class,
                    [
                        'label' => false,
                        'required' => true,
                        'attr' => [
                            'placeholder' => 'Duration (minutes)',
                            'class' => 'form-control'
                        ],
                        'constraints' => [
                            new NotBlank(['message' => 'You need to specify the duration of your event.']),
                            new Positive(['message' => 'Duration has to be a positive number.'])
                        ],
                    ]
                )
                ->add(
                    'timezone',
                    TimezoneType::class,
                    [
                        'label' => false,
                        'placeholder' => $user->getTimezone() ? Timezones::getName($user->getTimezone()) : 'Choose time zone',
                        'intl' => true,
                        'required' => false,
                        'attr' => [
                            'class' => 'form-control',
                        ],
                        'constraints' => [
                            new NotBlank(['message' => 'You need to specify your time zone.']),
                        ],
                        'empty_data' => $user->getTimezone() ?? '',
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
                );
        }
    }

    public function onPostSetData(FormEvent $event): void
    {
        /** @var Event|null  $onlineEvent */
        $onlineEvent = $event->getData();

        if ($onlineEvent && $onlineEvent->getPublished() === null) {
            $start = $onlineEvent->getStart();
            $event->getForm()->get('date')->setData($start->format('F d, Y'));
            $event->getForm()->get('time')->setData($start->format('h:i A'));
        }
    }

    public function onSubmitData(FormEvent $event): void
    {
        /** @var Event|null $onlineEvent */
        $onlineEvent = $event->getData();

        if ($onlineEvent && $onlineEvent->getPublished() === null) {
            $date = $event->getForm()->get('date')->getData();
            $time = $event->getForm()->get('time')->getData();
            $dateTime = new \DateTime($date .' '.$time);

            if ($dateTime < new \DateTime()) {
                $event->getForm()->get('date')->addError(new FormError('Date of new Event cannot be in the past'));
            }

            $onlineEvent->setStart($dateTime);
        }
    }
}
