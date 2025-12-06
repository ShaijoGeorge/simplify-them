<?php

namespace App\Controller\Admin;

use App\Entity\Agency;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AgencyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Agency::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // LEFT COLUMN: IDENTITY
        yield FormField::addColumn(6);
        
        yield FormField::addFieldset('Business Identity')
            ->setIcon('fa fa-building')
            ->setHelp('Core identification details');

        yield TextField::new('businessName', 'Agency Name')->setColumns(12);
        yield TextField::new('agencyCode', 'Agency Code')->setColumns(12);
        yield TextField::new('ownerName', 'Owner / Agent Name')->setColumns(12);

        // RIGHT COLUMN: OPERATIONS
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Operational Details')
            ->setIcon('fa fa-info-circle');

        yield TextField::new('mobile', 'Contact No')
            ->setColumns(12)
            ->setHelp('Primary contact number');

        yield TextField::new('licBranchCode', 'Branch Code')
            ->setColumns(12);
            
        yield BooleanField::new('isActive', 'Account Status')
            ->setLabel('Active Account')
            ->setColumns(12); // Render as a simple Yes/No badge or switch
    }
}