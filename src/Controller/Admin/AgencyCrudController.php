<?php

namespace App\Controller\Admin;

use App\Entity\Agency;
use App\Service\PermissionCheckerService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AgencyCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'agencies';
    }

    public static function getEntityFqcn(): string
    {
        return Agency::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Agency')
            ->setEntityLabelInPlural('Agencies');
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
        
        yield FormField::addFieldset('Business Identity')
            ->setIcon('fa fa-building')
            ->setHelp('Core identification details');

        yield TextField::new('businessName', 'Agency Name')->setColumns(12);
        yield TextField::new('agencyCode', 'Agency Code')->setColumns(12);
        yield TextField::new('ownerName', 'Owner / Agent Name')->setColumns(12);

        // RIGHT COLUMN: OPERATIONS
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Operational Details')
            ->setIcon('fa fa-info-circle');

        yield TextField::new('mobile', 'Contact No')
            ->setColumns(12)
            ->setFormTypeOption('attr', [
                'data-intl-phone' => 'true',
                'data-default-country' => 'in',
            ])
            ->setHelp('Primary contact number');

        yield TextField::new('licBranchCode', 'Branch Code')
            ->setColumns(12);

        yield TextField::new('panNumber', 'PAN Number')
            ->setColumns(12)
            ->setHelp('10-char PAN (e.g. ABCDE1234F). TDS = 5 % with PAN, 20 % without.')
            ->setFormTypeOption('attr', ['maxlength' => 10, 'style' => 'text-transform:uppercase']);
            
        yield BooleanField::new('isActive', 'Account Status')
            ->setLabel('Active Account')
            ->setColumns(12); // Render as a simple Yes/No badge or switch
    }
}
