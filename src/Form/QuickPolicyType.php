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
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class QuickPolicyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // CLIENT FIELDS
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'constraints' => [
                    new Assert\NotBlank(message: 'Name is required.'),
                    new Assert\Regex(
                        pattern: '/^[a-zA-Z.\s]+$/',
                        message: 'Name must contain only letters, dots, and spaces.',
                    ),
                ],
            ])
            ->add('mobile', TextType::class, [
                'label' => 'Mobile Number',
                'attr' => [
                    'data-intl-phone' => 'true',
                    'data-default-country' => 'in',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Mobile Number is required.'),
                    new Assert\Regex(
                        pattern: '/^\+?[1-9]\d{7,14}$/',
                        message: 'Enter a valid international mobile number.',
                    ),
                ],
            ])
            ->add('dob', DateType::class, [
                'label' => 'Date of Birth',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['max' => date('Y-m-d')],
                'constraints' => [
                    new Assert\LessThan(
                        value: 'today',
                        message: 'Date of Birth must be in the past.',
                    ),
                    new Assert\GreaterThanOrEqual(
                        value: '1900-01-01',
                        message: 'Date of Birth must not be before 1900.',
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
                    new Assert\Regex(
                        pattern: '/^\d+$/',
                        message: 'Policy Number must contain only numbers.',
                    ),
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
                    new Assert\GreaterThanOrEqual(
                        value: '1900-01-01',
                        message: 'Commencement Date must not be before 1900.',
                    ),
                ],
            ])
            ->add('sumAssured', TextType::class, [
                'label' => 'Sum Assured',
                'constraints' => [
                    new Assert\NotBlank(message: 'Sum Assured is required.'),
                    new Assert\Regex(
                        pattern: '/^\d+(\.\d+)?$/',
                        message: 'Sum Assured must be a valid number.',
                    ),
                    new Assert\Positive(message: 'Sum Assured must be a positive number.'),
                ],
            ])
            ->add('licBranch', TextType::class, [
                'label' => 'LIC Branch',
                'required' => false,
                'constraints' => [
                    new Assert\Length(
                        max: 100,
                        maxMessage: 'LIC Branch cannot exceed {{ limit }} characters.',
                    ),
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
            ->add('annualPremium', TextType::class, [
                'label' => 'Annual Premium (Tabular)',
                'constraints' => [
                    new Assert\NotBlank(message: 'Annual Premium is required.'),
                    new Assert\Regex(
                        pattern: '/^\d+(\.\d+)?$/',
                        message: 'Annual Premium must be a valid number.',
                    ),
                    new Assert\Positive(message: 'Annual Premium must be a positive number.'),
                ],
            ])
            ->add('basicPremium', TextType::class, [
                'label' => 'Modal Premium Amount',
                'required' => false,
                'constraints' => [
                    new Assert\Regex(
                        pattern: '/^\d+(\.\d+)?$/',
                        message: 'Modal Premium must be a valid number.',
                    ),
                    new Assert\Positive(message: 'Modal Premium must be a positive number.'),
                ],
            ])

        // NOMINEE FIELDS (all optional - a Nominee is created only if nomineeName is filled)
            ->add('nomineeName', TextType::class, [
                'label' => 'Nominee Name',
                'required' => false,
                'constraints' => [
                    new Assert\Length(
                        max: 200,
                        maxMessage: 'Nominee Name cannot exceed {{ limit }} characters.',
                    ),
                    new Assert\Regex(
                        pattern: '/^[a-zA-Z\s]*$/',
                        message: 'Nominee Name must contain only letters and spaces.',
                    ),
                ],
            ])
            ->add('nomineeRelationship', ChoiceType::class, [
                'label' => 'Relationship',
                'required' => false,
                'placeholder' => 'Select Relationship',
                'choices' => [
                    'Spouse' => 'SPOUSE',
                    'Son' => 'SON',
                    'Daughter' => 'DAUGHTER',
                    'Father' => 'FATHER',
                    'Mother' => 'MOTHER',
                    'Brother' => 'BROTHER',
                    'Sister' => 'SISTER',
                    'Other' => 'OTHER',
                ],
            ])
            ->add('nomineeSharePercentage', NumberType::class, [
                'label' => 'Share Percentage (%)',
                'required' => false,
                'constraints' => [
                    new Assert\Callback(function ($value, ExecutionContextInterface $context) {
                        if ($value === null || $value === '') {
                            return; // optional
                        }
                        $num = (float) $value;
                        if ($num < 1 || $num > 100) {
                            $context->buildViolation('Share Percentage must be between 1 and 100.')
                                ->addViolation();
                        }
                    }),
                ],
            ]);
    }
}
