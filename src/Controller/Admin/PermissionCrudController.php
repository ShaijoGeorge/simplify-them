<?php

namespace App\Controller\Admin;

use App\Entity\Permission;
use App\Service\PermissionCheckerService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

class PermissionCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'permissions';
    }

    public static function getEntityFqcn(): string
    {
        return Permission::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Permission')
            ->setEntityLabelInPlural('Permissions');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        if ($this->permissionChecker->hasPermission($this->getModuleKey(), 'view')) {
            $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        }

        return $actions;
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
