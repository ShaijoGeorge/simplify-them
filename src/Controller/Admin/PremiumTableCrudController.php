<?php

namespace App\Controller\Admin;

use App\Entity\PremiumTable;
use App\Service\PermissionCheckerService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PremiumTableCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'premium_tables';
    }

    public static function getEntityFqcn(): string
    {
        return PremiumTable::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Premium Table')
            ->setEntityLabelInPlural('Premium Tables')
            ->setSearchFields(['licPlan.planName', 'licPlan.tableNumber', 'entryAge', 'policyTerm'])
            ->setDefaultSort(['licPlan' => 'ASC', 'entryAge' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        // Master data - only ROLE_SUPER_ADMIN may create / edit / delete
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $actions->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
        }

        if ($this->permissionChecker->hasPermission($this->getModuleKey(), 'view')) {
            $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        // LEFT COLUMN: PLAN & PARAMETERS
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Plan & Parameters')
            ->setIcon('fa fa-layer-group')
            ->setHelp('Identify which plan, entry age, and policy term this rate applies to');

        yield AssociationField::new('licPlan', 'LIC Plan')
            ->setRequired(true)
            ->setColumns(12)
            ->setHelp('Select the LIC plan (e.g. 914 - New Endowment Plan)');

        yield NumberField::new('entryAge', 'Entry Age')
            ->setRequired(true)
            ->setColumns(6)
            ->setHelp('Age of the life assured at policy commencement');

        yield NumberField::new('policyTerm', 'Policy Term (Years)')
            ->setRequired(true)
            ->setColumns(6)
            ->setHelp('Duration of the policy in years');

        // RIGHT COLUMN: PREMIUM RATE
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Premium Rate')
            ->setIcon('fa fa-indian-rupee-sign')
            ->setHelp('Annual premium per Rs. 1,000 Sum Assured');

        yield NumberField::new('annualPremiumPerThousand', 'Annual Premium per ₹1000 SA')
            ->setRequired(true)
            ->setNumDecimals(2)
            ->setColumns(12)
            ->setHelp('e.g. 52.60 means ₹52.60 per ₹1,000 Sum Assured per year');

        // AUDIT INFO
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Audit Info')
            ->setIcon('fa fa-database')
            ->hideOnForm();

        yield TextField::new('createdBy', 'Created By')
            ->hideOnForm();

        yield DateField::new('createdAt', 'Created At')
            ->hideOnForm();

        yield TextField::new('updatedBy', 'Updated By')
            ->hideOnForm();

        yield DateField::new('updatedAt', 'Updated At')
            ->hideOnForm();
    }
}
