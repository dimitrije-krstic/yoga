<?php
declare(strict_types=1);

namespace App\Form\Cockpit\UserAccount;

use App\Entity\UserInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Url;

class UserInfoType extends AbstractType
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'introduction',
                TextareaType::class,
                [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Something more about yourself...',
                        'class' => 'form-control',
                        'rows' => 10
                    ],
                ]
            )
            ->add(
                'personalWebsite',
                UrlType::class,
                [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Personal Website'
                    ],
                    'constraints' => [
                        new Url()
                    ],
                    'default_protocol' => 'https',
                ]
            )
            ->add(
                'facebookAccount',
                UrlType::class,
                [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Facebook'
                    ],
                    'constraints' => [
                        new Url()
                    ],
                    'default_protocol' => 'https',
                ]
            )
            ->add(
                'youtubeAccount',
                UrlType::class,
                [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Youtube Channel'
                    ],
                    'constraints' => [
                        new Url()
                    ],
                    'default_protocol' => 'https',
                ]
            )
            ->add(
                'instagramAccount',
                UrlType::class,
                [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Instagram'
                    ],
                    'constraints' => [
                        new Url()
                    ],
                    'default_protocol' => 'https',
                ]
            )
            ->add(
                'twitterAccount',
                UrlType::class,
                [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Twitter'
                    ],
                    'constraints' => [
                        new Url()
                    ],
                    'default_protocol' => 'https',
                ]
            )
            ->add(
                'linkedinAccount',
                UrlType::class,
                [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'LinkedIn'
                    ],
                    'constraints' => [
                        new Url()
                    ],
                    'default_protocol' => 'https',
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserInfo::class,
        ]);
    }
}
