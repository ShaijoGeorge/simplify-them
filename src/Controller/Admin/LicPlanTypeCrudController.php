<?php

namespace App\Controller\Admin;

use App\Entity\LicPlanType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LicPlanTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LicPlanType::class;
    }

    public function configureFields(string $pageName): iterable
    {   
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Plan Type Information')
            ->setIcon('fa fa-layer-group')
            ->setHelp('Classification of LIC policies');
            
        yield TextField::new('name', 'Plan Type Name');
        yield TextEditorField::new('description');
    }
}
