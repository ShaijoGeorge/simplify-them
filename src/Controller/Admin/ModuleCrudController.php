<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use App\Service\PermissionCheckerService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ModuleCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'modules';
    }

    public static function getEntityFqcn(): string
    {
        return Module::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Module')
            ->setEntityLabelInPlural('Modules');
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
        yield FormField::addColumn(6);
        yield FormField::addFieldset('Modules')
            ->setIcon('fa fa-cube');

        yield IdField::new('id')->hideOnForm();
        
        yield TextField::new('name', 'Module Name')
            ->setHelp('Display name (e.g., "LIC Plans")');
            
        yield TextField::new('moduleKey', 'Module Key')
            ->setHelp('Unique code used by developers (e.g., "lic_plans"). Seperate words with underscore (_).');
    }
}
