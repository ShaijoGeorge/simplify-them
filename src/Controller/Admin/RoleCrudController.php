<?php

namespace App\Controller\Admin;

use App\Entity\Role;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RoleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Role::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Role Information')
            ->setIcon('fa fa-user-tag')
            ->setHelp('Define custom roles for your agency staff');

        yield IdField::new('id')->hideOnForm();
        
        yield TextField::new('name', 'Role Name')
            ->setColumns(12)
            ->setHelp('e.g., "Data Entry Clerk"');

        yield AssociationField::new('agency', 'Agency')
            ->setColumns(12)
            ->setRequired(true);
    }
}