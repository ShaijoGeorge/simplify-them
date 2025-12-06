<?php

namespace App\Controller\Admin;

use App\Entity\LicPlan;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LicPlanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LicPlan::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // LEFT COLUMN: IDENTITY
        yield FormField::addColumn(6);
        
        yield FormField::addFieldset('Plan Identity')
            ->setIcon('fa fa-id-card')
            ->setHelp('Core details of the LIC Table');

        yield TextField::new('tableNumber', 'Table Number')
            ->setColumns(12)
            ->setHelp('e.g. 914');
            
        yield TextField::new('planName', 'Plan Name')
            ->setColumns(12)
            ->setHelp('e.g. New Endowment Plan');

        // RIGHT COLUMN: CONFIGURATION
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Configuration')
            ->setIcon('fa fa-cogs');

        yield TextField::new('type', 'Plan Type')
            ->setColumns(12)
            ->setHelp('e.g. Endowment, Money Back, Term Assurance');

        yield BooleanField::new('isActive', 'Plan Status')
            ->setLabel('Active for New Policies')
            ->setColumns(12);

        // FULL WIDTH: DESCRIPTION 
        yield FormField::addColumn(12);
        
        yield FormField::addFieldset('Plan Description')
            ->setIcon('fa fa-align-left');

        yield TextEditorField::new('description')
            ->setColumns(12)
            ->setNumOfRows(6);
    }
}