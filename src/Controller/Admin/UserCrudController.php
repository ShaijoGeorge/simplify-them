<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // LEFT COLUMN: PROFILE
        yield FormField::addColumn(6);

        yield FormField::addFieldset('User Profile')
            ->setIcon('fa fa-user');

        yield TextField::new('fullName', 'Full Name')->setColumns(12);
        yield TextField::new('email', 'Email Address')->setColumns(12);

        // RIGHT COLUMN: ACCESS
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Access Control')
            ->setIcon('fa fa-shield-alt');

        yield ChoiceField::new('roles', 'System Role')
            ->setChoices([
                'Super Admin' => 'ROLE_SUPER_ADMIN',
                'Agency Admin' => 'ROLE_ADMIN',
                'Staff Member' => 'ROLE_USER',
            ])
            ->allowMultipleChoices()
            ->setColumns(12);

        yield AssociationField::new('agency')
            ->setLabel('Linked Agency')
            ->setHelp('The agency this user belongs to')
            ->setColumns(12);
    }
}