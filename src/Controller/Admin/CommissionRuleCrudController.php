<?php

namespace App\Controller\Admin;

use App\Entity\CommissionRule;
use App\Service\PermissionCheckerService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;

class CommissionRuleCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'commission_rules';
    }

    public static function getEntityFqcn(): string
    {
        return CommissionRule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commission Rule')
            ->setEntityLabelInPlural('Commission Rules')
            ->setSearchFields(['licPlan.planName']);
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
        yield IdField::new('id')
            ->onlyOnIndex();

        // LEFT COLUMN
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Policy Mapping')
            ->setIcon('fa fa-file-contract');

        yield AssociationField::new('licPlan', 'LIC Plan')
            ->setColumns(12);

        yield NumberField::new('policyYearFrom', 'From Policy Year')
            ->setColumns(6);

        yield NumberField::new('policyYearTo', 'To Policy Year')
            ->setColumns(6);

        // RIGHT COLUMN
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Term & Commission')
            ->setIcon('fa fa-percent');

        yield NumberField::new('minTerm', 'Min Term (Years)')
            ->setColumns(6)
            ->setHelp('Minimum policy term');

        yield NumberField::new('maxTerm', 'Max Term (Years)')
            ->setColumns(6)
            ->setHelp('Maximum policy term');

        yield PercentField::new('commissionRate', 'Commission Rate (%)')
            ->setNumDecimals(2)
            ->setStoredAsFractional(false)
            ->setColumns(12);
    }
}
