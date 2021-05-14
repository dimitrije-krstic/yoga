<?php
declare(strict_types=1);

namespace App\Form\Cockpit\Post;

use App\Entity\Post;
use App\Form\DataTransformer\TagToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Url;

class PostType extends AbstractType
{
    private TagToStringTransformer $postTagToStringTransformer;
    private Security $security;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        TagToStringTransformer $postTagToStringTransformer,
        AuthorizationCheckerInterface $authorizationChecker,
        Security $security
    ) {
        $this->postTagToStringTransformer = $postTagToStringTransformer;
        $this->security = $security;
        $this->authorizationChecker = $authorizationChecker;
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
                    ],
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                    ],
                ]
            )
            ->add(
                'category',
                ChoiceType::class,
                [
                    'label' => false,
                    'required' => true,
                    'attr' => [
                        'class' => 'form-control'
                    ],
                    'choices' => array_merge(['Choose Category' => 0], array_flip(Post::CATEGORY)),
                    'constraints' => [
                        new NotNull(['message' => 'You have to select main category for your post']),
                        new Range([
                            'min' => 1,
                            'max' => 7,
                            'minMessage' => 'You have to select main category for your post',
                            'maxMessage' => 'You have to select main category for your post'
                        ])
                    ]
                ]
            )
            ->add(
                'content',
                TextareaType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'Post content goes here...',
                        'class' => 'form-control postContent',
                        'rows' => 14
                    ],
                    'required' => false,
                ]
            )
            ->add(
                'tags',
                TextareaType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'Tag your post for better search...',
                        'class' => 'form-control',
                        'style' => 'height:100%; width:100%'
                    ],
                    'required' => false,
                ]
            )
            ->add(
                'save',
                SubmitType::class,
                [
                    'label' => 'Save',
                    'attr'=> [
                        'class' => 'btn btn-primary'
                    ]
                ]
            )
        ;

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $builder->add(
                'webPostAuthorName',
                TextType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'Post Author Name',
                        'class' => 'form-control',
                    ],
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                    ],
                ]
            )
            ->add(
                'webPostAuthorLink',
                TextType::class,
                [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'Post Author Link',
                        'class' => 'form-control',
                    ],
                    'required' => false,
                    'constraints' => [
                        new Url(),
                    ],
                ]
            );
        }

        $builder->get('tags')->addModelTransformer($this->postTagToStringTransformer);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'allow_extra_fields' => true,
            'categories' => [],
            'empty_data' => function (FormInterface $form) {
                return new Post($this->security->getUser());
            },
        ]);
    }

    public function onPreSetData(FormEvent $event): void
    {
        /** @var Post|null $post */
        $post = $event->getData();

        if (!$post) {
            $event->getForm()
                ->add(
                    'imageFile',
                    FileType::class,
                    [
                        'mapped' => false,
                        'label' => false,
                        'required' => false,
                        'attr' => [
                            'style' => 'display:none;',
                            'class' => 'user-image-input'
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
    }
}
