<?php

namespace App\Controller\Admin;

use App\Entity\BonusRate;
use App\Service\PermissionCheckerService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BonusRateCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'bonus_rates';
    }

    public static function getEntityFqcn(): string
    {
        return BonusRate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Bonus Rate')
            ->setEntityLabelInPlural('Bonus Rates')
            ->setSearchFields(['licPlan.planName', 'licPlan.tableNumber', 'financialYear'])
            ->setDefaultSort(['financialYear' => 'DESC']);
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
        // LEFT COLUMN: PLAN & YEAR
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Plan & Financial Year')
            ->setIcon('fa fa-calendar-alt')
            ->setHelp('Identify which plan and year this bonus rate applies to');

        yield AssociationField::new('licPlan', 'LIC Plan')
            ->setRequired(true)
            ->setColumns(6)
            ->setHelp('Select the LIC plan (e.g. 914 - New Endowment Plan)');

        yield TextField::new('financialYear', 'Financial Year')
            ->setRequired(true)
            ->setColumns(6)
            ->setHelp('e.g. "2023-24". LIC announces bonus rates annually after valuation.');

        // RIGHT COLUMN: BONUS VALUES
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Bonus Values (per Rs. 1000 SA)')
            ->setIcon('fa fa-indian-rupee-sign')
            ->setHelp('All values are per Rs. 1000 Sum Assured');

        yield NumberField::new('simpleReversionaryBonus', 'Simple Reversionary Bonus')
            ->setRequired(true)
            ->setNumDecimals(2)
            ->setColumns(6)
            ->setHelp('Per Rs. 1000 SA per year. e.g. 48 means Rs. 48 per Rs. 1000 SA');

        yield NumberField::new('finalAdditionalBonus', 'Final Additional Bonus (FAB)')
            ->setNumDecimals(2)
            ->setColumns(6)
            ->setHelp('Per Rs. 1000 SA for policies completing 15+ years. Announced at maturity.');

        yield NumberField::new('loyaltyAddition', 'Loyalty Addition')
            ->setNumDecimals(2)
            ->setColumns(6)
            ->setHelp('Extra addition for policies in force for long duration (plan-specific).');

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
