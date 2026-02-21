<?php

namespace App\Form;

use App\Entity\LicPlan;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class QuickPolicyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // CLIENT FIELDS
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'constraints' => [
                    new Assert\NotBlank(message: 'First Name is required.'),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'required' => false,
            ])
            ->add('mobile', TextType::class, [
                'label' => 'Mobile Number',
                'constraints' => [
                    new Assert\NotBlank(message: 'Mobile Number is required.'),
                    new Assert\Regex(
                        pattern: '/^\d{10,15}$/',
                        message: 'Enter a valid mobile number (10–15 digits).',
                    ),
                ],
            ])
            ->add('dob', DateType::class, [
                'label' => 'Date of Birth',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\LessThan(
                        value: 'today',
                        message: 'Date of Birth must be in the past.',
                    ),
                ],
            ])

        // POLICY FIELDS
            ->add('licPlan', EntityType::class, [
                'class' => LicPlan::class,
                'label' => 'LIC Plan',
                'placeholder' => 'Select a Plan',
                'constraints' => [
                    new Assert\NotBlank(message: 'Please select a LIC Plan.'),
                ],
            ])
            ->add('policyNumber', TextType::class, [
                'label' => 'Policy Number',
                'constraints' => [
                    new Assert\NotBlank(message: 'Policy Number is required.'),
                    new Assert\Length(
                        min: 5,
                        max: 50,
                        minMessage: 'Policy Number must be at least {{ limit }} characters.',
                        maxMessage: 'Policy Number cannot exceed {{ limit }} characters.',
                    ),
                ],
            ])
            ->add('commencementDate', DateType::class, [
                'label' => 'Commencement Date (DOC)',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(message: 'Commencement Date is required.'),
                ],
            ])
            ->add('sumAssured', TextType::class, [
                'label' => 'Sum Assured',
                'constraints' => [
                    new Assert\NotBlank(message: 'Sum Assured is required.'),
                    new Assert\Positive(message: 'Sum Assured must be a positive number.'),
                ],
            ])
            ->add('policyTerm', NumberType::class, [
                'label' => 'Policy Term',
                'constraints' => [
                    new Assert\NotBlank(message: 'Policy Term is required.'),
                    new Assert\Positive(message: 'Policy Term must be a positive number.'),
                ],
            ])
            ->add('premiumPayingTerm', NumberType::class, [
                'label' => 'Premium Paying Term',
                'constraints' => [
                    new Assert\NotBlank(message: 'Premium Paying Term is required.'),
                    new Assert\Positive(message: 'Premium Paying Term must be a positive number.'),
                ],
            ])
            ->add('premiumMode', ChoiceType::class, [
                'label' => 'Payment Mode',
                'choices' => [
                    'Yearly' => 'YLY',
                    'Half-Yearly' => 'HLY',
                    'Quarterly' => 'QLY',
                    'Monthly (NACH)' => 'NACH',
                    'Single' => 'SINGLE'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please select a Payment Mode.'),
                ],
            ])
            ->add('basicPremium', TextType::class, [
                'label' => 'Basic Premium Amount',
                'constraints' => [
                    new Assert\NotBlank(message: 'Basic Premium is required.'),
                    new Assert\Positive(message: 'Basic Premium must be a positive number.'),
                ],
            ]);
    }
}
