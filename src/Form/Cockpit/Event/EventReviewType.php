<?php
declare(strict_types=1);

namespace App\Form\Cockpit\Event;

use App\Entity\EventReview;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;

class EventReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'text',
                TextareaType::class,
                [
                    'label' => false,
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Write anonymous review...',
                        'class' => 'form-control',
                        'rows' => 4
                    ],
                    'empty_data' => '',
                    'constraints' => [
                        new NotBlank(['message' => 'Review cannot be blank']),
                    ],
                ]
            )
            ->add(
                'grade',
                ChoiceType::class,
                [
                    'label' => false,
                    'required' => true,
                    'attr' => [
                        'class' => 'form-control'
                    ],
                    'choices' => [
                        'Excellent' => 5,
                        'Very Good' => 4,
                        'Good' => 3,
                        'Satisfactory' => 2,
                        'Below expectations' => 1
                    ],
                    'constraints' => [
                        new NotNull(['message' => 'You should choose one of the available grades']),
                        new Range(['min' => 1, 'max' => 5])
                    ]
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventReview::class,
        ]);
    }
}
