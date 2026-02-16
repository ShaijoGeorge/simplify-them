<?php

namespace App\Controller\Admin;

use App\Entity\LicPlanType;
use App\Service\PermissionCheckerService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LicPlanTypeCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'plan_types';
    }

    public static function getEntityFqcn(): string
    {
        return LicPlanType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Plan Type')
            ->setEntityLabelInPlural('Plan Types');
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

        yield FormField::addFieldset('Plan Type Information')
            ->setIcon('fa fa-layer-group')
            ->setHelp('Classification of LIC policies');
            
        yield TextField::new('name', 'Plan Type Name');
        yield TextEditorField::new('description');
    }
}
