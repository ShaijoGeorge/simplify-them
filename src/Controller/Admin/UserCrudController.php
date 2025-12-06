<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            
            TextField::new('email'),
            TextField::new('fullName'),
            
            AssociationField::new('agency')->setRequired(false),

            ChoiceField::new('roles')
                ->setChoices([
                    'Super Admin' => 'ROLE_SUPER_ADMIN',
                    'Agency Admin' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_USER',
                ])
                ->allowMultipleChoices()
                ->renderExpanded(),
        ];
    }
}
