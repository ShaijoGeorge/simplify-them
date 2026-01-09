<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        // LEFT COLUMN: PROFILE
        yield FormField::addColumn(6);

        yield FormField::addFieldset('User Profile')
            ->setIcon('fa fa-user');

        yield TextField::new('fullName', 'Full Name')->setColumns(12);
        yield TextField::new('email', 'Email Address')->setColumns(12);
        yield TextField::new('password', 'Password')
            ->setFormType(PasswordType::class)
            ->setColumns(12)
            ->setFormTypeOption('mapped', false) // Prevents the "null" error
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->onlyOnForms();

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

    // Manually Hash Password on Create
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    // Manually Hash Password on Edit
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPassword($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPassword(User $user): void
    {
        // Get the plain password from the form manually
        $request = $this->getContext()->getRequest();
        $formData = $request->request->all('User');
        $plainPassword = $formData['password'] ?? null;

        // Only update if the user actually typed a password
        if (!empty($plainPassword)) {
            $hashedPassword = $this->userPasswordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }
    }
}