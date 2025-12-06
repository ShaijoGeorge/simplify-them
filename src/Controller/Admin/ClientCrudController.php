<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ClientCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Client::class;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var User $user */
        $user = $this->getUser();
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles());

        // LEFT COLUMN: IDENTITY
        yield FormField::addColumn(6);
        
        yield FormField::addFieldset('Personal Information')
            ->setIcon('fa fa-user');

        yield TextField::new('firstName', 'First Name')->setColumns(6);
        yield TextField::new('lastName', 'Last Name')->setColumns(6);
        yield DateField::new('dob', 'Date of Birth')->setColumns(12);

        yield FormField::addFieldset('Family Grouping')
            ->setIcon('fa fa-users')
            ->setHelp('Link this client to a family head for group management');

        yield AssociationField::new('headOfFamily', 'Head of Family')
            ->setColumns(12);

        // RIGHT COLUMN: CONTACT
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Contact Details')
            ->setIcon('fa fa-address-book');

        yield TelephoneField::new('mobile', 'Mobile No')->setColumns(6);
        yield EmailField::new('email', 'Email')->setColumns(6);

        yield FormField::addFieldset('Address & Location')
            ->setIcon('fa fa-map-marker-alt');

        yield TextareaField::new('address')->hideOnIndex()->setColumns(12);
        yield TextField::new('city')->setColumns(6);
        yield TextField::new('pincode')->setColumns(6);

        // --- SYSTEM FIELDS ---
        if ($isSuperAdmin) {
            yield FormField::addFieldset('System Metadata')->setIcon('fa fa-database');
            yield AssociationField::new('agency')->setColumns(12)->setHelp('Super Admin Only: Reassign policy to a different agency');
        } else {
            yield AssociationField::new('agency')->hideOnForm()->hideOnIndex();
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Check if we are saving a Client
        if ($entityInstance instanceof Client) {
            // Get the current User and their Agency
            $user = $this->getUser();
            if ($user && $user->getAgency()) {
                // Set the Agency automatically
                $entityInstance->setAgency($user->getAgency());
            }
        }

        // Continue with the standard save process
        parent::persistEntity($entityManager, $entityInstance);
    }
}
