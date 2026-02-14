<?php

namespace App\Controller\Admin;

use App\Entity\Policy;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Doctrine\ORM\QueryBuilder;

class PolicyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Policy::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // Eager load Client to filter out Policies with inaccessible Clients (prevents EntityNotFoundException)
        $qb->innerJoin('entity.client', 'client');
        $qb->addSelect('client');

        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function createEntity(string $entityFqcn)
    {
        $policy = new Policy();
        $user = $this->getUser();

        if ($user && $user->getAgency()) {
            $policy->setAgency($user->getAgency());
        }

        return $policy;
    }

    public function configureFields(string $pageName): iterable
    {
        // Get current User to check permissions/roles
        /** @var User $user */
        $user = $this->getUser();
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles());

        //LEFT COLUMN (6/12): CONTRACT DETAILS
        yield FormField::addColumn(6);
        
        yield FormField::addFieldset('Contract Information')
            ->setIcon('fa fa-file-signature')
            ->setHelp('Basic identification and ownership details');

        yield TextField::new('policyNumber', 'Policy Number')
            ->setColumns(12); // Full width within the left column

        yield AssociationField::new('client', 'Policy Holder')
            ->setColumns(12)
            ->setRequired(true);

        yield AssociationField::new('licPlan', 'Plan Table')
            ->setColumns(12)
            ->setRequired(true);

        yield DateField::new('commencementDate', 'Date of Commencement (DOC)')
            ->setColumns(12)
            ->hideOnIndex();


        yield FormField::addFieldset('Terms & Conditions')
            ->setIcon('fa fa-sliders-h');

        yield NumberField::new('policyTerm', 'Policy Term (Years)')
            ->setColumns(6)
            ->hideOnIndex();

        yield NumberField::new('premiumPayingTerm', 'PPT (Years)')
            ->setColumns(6)
            ->hideOnIndex();

        yield ChoiceField::new('premiumMode', 'Payment Mode')
            ->setChoices([
                'Yearly' => 'YLY',
                'Half-Yearly' => 'HLY',
                'Quarterly' => 'QLY',
                'Monthly (NACH)' => 'NACH',
                'Single' => 'SINGLE'
            ])
            ->renderAsBadges()
            ->setColumns(12);


        // RIGHT COLUMN: FINANCIALS & STATUS
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Valuation & Premiums')
            ->setIcon('fa fa-rupee-sign')
            ->setHelp('Financial values associated with this policy');

        yield MoneyField::new('sumAssured', 'Sum Assured')
            ->setCurrency('INR')
            ->setColumns(12)
            ->hideOnIndex();

        // Group premiums visually
        yield MoneyField::new('basicPremium', 'Basic Premium')
            ->setCurrency('INR')
            ->setColumns(4)
            ->setHelp('Enter amount BEFORE tax')
            ->hideOnIndex();
        
        yield MoneyField::new('gst', 'GST')
            ->setCurrency('INR')
            ->setColumns(4)
            ->setDisabled(true);
        
        yield MoneyField::new('totalPremium', 'Total')
            ->setCurrency('INR')
            ->setColumns(4)
            ->setDisabled(true)
            ->setHelp('Auto-calculated (Basic + GST)');


        yield FormField::addFieldset('Status & Tracking')
            ->setIcon('fa fa-clock');

        yield ChoiceField::new('status')
            ->setChoices([
                'In Force' => 'IN_FORCE',
                'Lapsed' => 'LAPSED',
                'Matured' => 'MATURED'
            ])
            ->renderAsBadges() // Nice color coding
            ->setColumns(12);

        yield DateField::new('nextDueDate', 'Next Premium Due')
            ->setColumns(6);

        yield DateField::new('maturityDate', 'Maturity Date')
            ->setColumns(6)
            ->hideOnIndex();

            
        // META DATA
        yield FormField::addFieldset('System Metadata')->setIcon('fa fa-database');
        yield AssociationField::new('agency')
            ->setColumns(12)
            ->setHelp('Super Admin Only: Reassign policy to a different agency');
        
        $agencyField = AssociationField::new('agency', 'Agency')
            ->setColumns(12);

        $user = $this->getUser();
        if ($user->isAdministrator()) {
            yield $agencyField
                ->setRequired(true)
                ->setHelp('Super Admin Only: Assign user to a specific agency');
        } else {
            yield $agencyField
                ->hideOnIndex()
                ->setDisabled(true);
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Policy) {
            /** @var User $user */
            $user = $this->getUser();
            
            // Only auto-set agency if the user HAS an agency and didn't manually set one (admin case)
            if ($user && $user->getAgency() && $entityInstance->getAgency() === null) {
                $entityInstance->setAgency($user->getAgency());
            }
        }
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Policy) {
            $user = $this->getUser();

            if ($user && $user->getAgency() && $entityInstance->getAgency() === null) {
                $entityInstance->setAgency($user->getAgency());
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')       // filtering by Status (Lapsed/InForce)
            ->add('nextDueDate')  // filtering by Date
            ->add('client')       // searching by Client
            ->add('agency');      // for Super Admin
    }
}