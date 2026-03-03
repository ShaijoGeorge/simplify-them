<?php

namespace App\Controller\Admin;

use App\Entity\LicPlan;
use App\Service\PermissionCheckerService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LicPlanCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'lic_plans';
    }

    public static function getEntityFqcn(): string
    {
        return LicPlan::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('LIC Plan')
            ->setEntityLabelInPlural('LIC Plans');
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

        yield AssociationField::new('planType', 'Plan Type')
            ->setColumns(12)
            ->setHelp('Select the category (e.g. Endowment, Money Back)');

        yield BooleanField::new('isSinglePremium', 'Single Premium Plan')
            ->setColumns(6)
            ->renderAsSwitch(false);

        yield BooleanField::new('isLimitedPremium', 'Limited Premium Plan')
            ->setColumns(6)
            ->renderAsSwitch(false);

        yield BooleanField::new('isActive', 'Plan Status')
            ->setLabel('Active for New Policies')
            ->setColumns(12)
            ->renderAsSwitch(false);

        // FULL WIDTH: DESCRIPTION 
        yield FormField::addColumn(12);
        
        yield FormField::addFieldset('Plan Description')
            ->setIcon('fa fa-align-left');

        // Show the WYSIWYG editor ONLY on the Create/Edit forms
        yield TextEditorField::new('description')
            ->setColumns(12)
            ->setNumOfRows(6)
            ->onlyOnForms();
            
        // Render the actual HTML ONLY on the Detail page
        yield TextField::new('description')
            ->renderAsHtml()
            ->onlyOnDetail();
    }
}
