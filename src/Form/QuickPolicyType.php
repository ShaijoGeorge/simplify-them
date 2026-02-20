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

class QuickPolicyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // CLIENT FIELDS
        $builder
            ->add('firstName', TextType::class, ['label' => 'First Name'])
            ->add('lastName', TextType::class, ['label' => 'Last Name', 'required' => false])
            ->add('mobile', TextType::class, ['label' => 'Mobile Number'])
            ->add('dob', DateType::class, [
                'label' => 'Date of Birth',
                'widget' => 'single_text',
            ])

        // POLICY FIELDS
            ->add('licPlan', EntityType::class, [
                'class' => LicPlan::class,
                'label' => 'LIC Plan',
                'placeholder' => 'Select a Plan'
            ])
            ->add('policyNumber', TextType::class, ['label' => 'Policy Number'])
            ->add('commencementDate', DateType::class, [
                'label' => 'Commencement Date (DOC)',
                'widget' => 'single_text',
            ])
            ->add('sumAssured', TextType::class, ['label' => 'Sum Assured'])
            ->add('policyTerm', NumberType::class, ['label' => 'Policy Term'])
            ->add('premiumPayingTerm', NumberType::class, ['label' => 'Premium Paying Term'])
            ->add('premiumMode', ChoiceType::class, [
                'label' => 'Payment Mode',
                'choices' => [
                    'Yearly' => 'YLY',
                    'Half-Yearly' => 'HLY',
                    'Quarterly' => 'QLY',
                    'Monthly (NACH)' => 'NACH',
                    'Single' => 'SINGLE'
                ]
            ])
            ->add('basicPremium', TextType::class, ['label' => 'Basic Premium Amount']);
    }
}