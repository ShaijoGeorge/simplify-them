<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
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
        return [
            // Personal Info
            TextField::new('firstName', 'First Name')->setColumns(6),
            TextField::new('lastName', 'Last Name')->setColumns(6),
            DateField::new('dob', 'Date of Birth')->setColumns(6),
            
            // Family Grouping (The smart part)
            AssociationField::new('headOfFamily', 'Head of Family')
                ->setHelp('Leave empty if this person is the Family Head')
                ->setColumns(6),

            // Contact Info
            TelephoneField::new('mobile', 'Mobile No')->setColumns(6),
            EmailField::new('email', 'Email')->setColumns(6),
            
            // Address
            TextareaField::new('address')->hideOnIndex()->setColumns(12),
            TextField::new('city')->setColumns(6),
            TextField::new('pincode')->setColumns(6),
            
            // We HIDE the Agency field because it's set automatically behind the scenes
            AssociationField::new('agency')->hideOnForm(),
        ];
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
