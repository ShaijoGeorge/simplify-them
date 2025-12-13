<?php

namespace App\Controller\Admin;

use App\Entity\CommissionRule;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CommissionRuleCrudController extends AbstractCrudController
{
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
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);

        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            return $actions->disable(Action::INDEX, Action::NEW, Action::EDIT, Action::DELETE);
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
