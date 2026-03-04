<?php

namespace App\Form;

use App\Entity\PolicyRider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Form type for the PolicyRider inline collection inside PolicyCrudController.
 */
class PolicyRiderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('riderType', ChoiceType::class, [
                'label' => 'Rider Type',
                'choices' => array_flip(PolicyRider::RIDER_TYPES),
                'placeholder' => 'Select rider…',
                'constraints' => [
                    new Assert\NotBlank(message: 'Please select a rider type.'),
                ],
            ])
            ->add('riderSumAssured', NumberType::class, [
                'label' => 'Rider Sum Assured (₹)',
                'required' => false,
                'scale' => 2,
                'attr' => ['placeholder' => 'e.g. 500000'],
            ])
            ->add('riderPremium', NumberType::class, [
                'label' => 'Annual Rider Premium (₹)',
                'scale' => 2,
                'attr' => ['placeholder' => 'e.g. 1200'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Rider premium is required.'),
                    new Assert\Positive(message: 'Rider premium must be positive.'),
                ],
            ])
            ->add('riderStartDate', DateType::class, [
                'label' => 'Start Date',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(message: 'Rider start date is required.'),
                ],
            ])
            ->add('riderEndDate', DateType::class, [
                'label' => 'End Date (optional)',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PolicyRider::class,
        ]);
    }
}