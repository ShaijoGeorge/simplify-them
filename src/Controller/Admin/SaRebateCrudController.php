<?php

namespace App\Controller\Admin;

use App\Entity\SaRebate;
use App\Service\PermissionCheckerService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SaRebateCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'sa_rebates';
    }

    public static function getEntityFqcn(): string
    {
        return SaRebate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('SA Rebate')
            ->setEntityLabelInPlural('SA Rebates')
            ->setSearchFields(['minSumAssured', 'maxSumAssured', 'rebatePerThousand'])
            ->setDefaultSort(['minSumAssured' => 'ASC']);
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
        // LEFT COLUMN: SA BAND
        yield FormField::addColumn(6);

        yield FormField::addFieldset('SA Band')
            ->setIcon('fa fa-sliders')
            ->setHelp('Define the Sum Assured range for this rebate band');

        yield NumberField::new('minSumAssured', 'Min Sum Assured (₹)')
            ->setRequired(true)
            ->setNumDecimals(2)
            ->setColumns(6)
            ->setHelp('Lower bound of the SA band (inclusive)');

        yield NumberField::new('maxSumAssured', 'Max Sum Assured (₹)')
            ->setNumDecimals(2)
            ->setColumns(6)
            ->setHelp('Upper bound (inclusive). Leave empty for the highest open-ended band.');

        // RIGHT COLUMN: REBATE
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Rebate')
            ->setIcon('fa fa-indian-rupee-sign')
            ->setHelp('Rebate deducted per ₹1,000 Sum Assured');

        yield NumberField::new('rebatePerThousand', 'Rebate per ₹1000 SA')
            ->setRequired(true)
            ->setNumDecimals(2)
            ->setColumns(6)
            ->setHelp('e.g. 2 means ₹2 rebate per ₹1,000 Sum Assured');

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
