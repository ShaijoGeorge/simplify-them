<?php

namespace App\Controller\Admin;

use App\Entity\Agency;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AgencyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Agency::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            
            TextField::new('businessName', 'Agency Name')->setColumns(6),
            TextField::new('agencyCode', 'Agency Code')->setColumns(6),
            
            TextField::new('ownerName', 'Agent Name')->setColumns(6),
            TextField::new('mobile', 'Contact No')->setColumns(6),
            
            TextField::new('licBranchCode', 'Branch Code')->hideOnIndex(),
            
            BooleanField::new('isActive', 'Account Active'),
        ];
    }
}
