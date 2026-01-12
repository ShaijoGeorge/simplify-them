<?php

namespace App\Controller\Admin;

use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RoleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Role::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Role Information')
            ->setIcon('fa fa-user-tag')
            ->setHelp('Define custom roles for your agency staff');

        yield IdField::new('id')->hideOnForm();
        
        yield TextField::new('name', 'Role Name')
            ->setColumns(12)
            ->setHelp('e.g., "Data Entry Clerk"');

        // Agency field - conditionally configure based on user role
        $agencyField = AssociationField::new('agency', 'Agency')
            ->setColumns(12);

        // Check if user is Super Admin
        /** @var User $user */
        $user = $this->getUser();
        if ($user && in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            // Super Admin: Show the field and make it required
            yield $agencyField
                ->setRequired(true)
                ->setHelp('Super Admin Only: Assign role to a specific agency');
        } else {
            // Regular User: Hide the field (will be set automatically)
            yield $agencyField
                ->hideOnIndex()
                ->setDisabled(true);
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Role) {
            $user = $this->getUser();
            
            // Only auto-set agency if the user HAS an agency and didn't manually set one (admin case)
            if ($user && $user->getAgency() && $entityInstance->getAgency() === null) {
                $entityInstance->setAgency($user->getAgency());
            }
        }
        
        parent::persistEntity($entityManager, $entityInstance);
    }
}