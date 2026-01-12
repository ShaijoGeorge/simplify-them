<?php

namespace App\Controller\Admin;

use App\Entity\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

class PermissionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Permission::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        // LEFT COLUMN
        yield FormField::addColumn(6);
        
        yield FormField::addFieldset('Permission Mapping')
            ->setIcon('fa fa-link')
            ->setHelp('Link a role to a module with specific access rights');

        yield AssociationField::new('role', 'Role')
            ->setColumns(12)
            ->setRequired(true);

        yield AssociationField::new('module', 'Module')
            ->setColumns(12)
            ->setRequired(true);

        // RIGHT COLUMN
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Access Rights')
            ->setIcon('fa fa-shield-alt')
            ->setHelp('Define what this role can do with this module');

        yield BooleanField::new('canView', 'View Access')
            ->setColumns(6)
            ->setHelp('Can see the module');

        yield BooleanField::new('canCreate', 'Create Access')
            ->setColumns(6)
            ->setHelp('Can add new records');

        yield BooleanField::new('canEdit', 'Edit Access')
            ->setColumns(6)
            ->setHelp('Can modify records');

        yield BooleanField::new('canDelete', 'Delete Access')
            ->setColumns(6)
            ->setHelp('Can remove records');
    }
}