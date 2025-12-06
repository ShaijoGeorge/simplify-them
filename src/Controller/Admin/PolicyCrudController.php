<?php

namespace App\Controller\Admin;

use App\Entity\Policy;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PolicyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Policy::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // Core Details
            TextField::new('policyNumber', 'Policy No')->setColumns(4),
            DateField::new('commencementDate', 'DOC')->setColumns(4),
            AssociationField::new('licPlan', 'Plan Table')->setColumns(4),
            
            // Client Link
            AssociationField::new('client', 'Policy Holder')->setColumns(6),
            
            // Terms
            NumberField::new('policyTerm', 'Term (Yrs)')->setColumns(3),
            NumberField::new('premiumPayingTerm', 'PPT (Yrs)')->setColumns(3),
            
            // Money Section
            MoneyField::new('sumAssured', 'Sum Assured')->setCurrency('INR')->setColumns(6),
            ChoiceField::new('premiumMode', 'Mode')->setChoices([
                'Yearly' => 'YLY',
                'Half-Yearly' => 'HLY',
                'Quarterly' => 'QLY',
                'Monthly (NACH)' => 'NACH',
                'Single' => 'SINGLE'
            ])->setColumns(6),

            // Premium Calculation
            MoneyField::new('basicPremium', 'Basic Prem')->setCurrency('INR')->setColumns(4),
            MoneyField::new('gst', 'GST')->setCurrency('INR')->setColumns(4),
            MoneyField::new('totalPremium', 'Total Prem')->setCurrency('INR')->setColumns(4),

            // Status & Dates
            ChoiceField::new('status', 'Status')->setChoices([
                'In Force' => 'IN_FORCE',
                'Lapsed' => 'LAPSED',
                'Matured' => 'MATURED'
            ])->renderAsBadges()->setColumns(6),
            
            DateField::new('nextDueDate', 'Next Due')->setColumns(3),
            DateField::new('maturityDate', 'Maturity Date')->setColumns(3),

            // Hidden SaaS Field
            AssociationField::new('agency')->hideOnForm(),
        ];
    }

    // SAAS LOGIC: Automatically link Policy to the logged-in Agent
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Policy) {
            $user = $this->getUser();
            if ($user && $user->getAgency()) {
                $entityInstance->setAgency($user->getAgency());
            }
        }
        parent::persistEntity($entityManager, $entityInstance);
    }
}