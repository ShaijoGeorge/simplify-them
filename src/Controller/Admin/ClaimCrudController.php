<?php

namespace App\Controller\Admin;

use App\Entity\Claim;
use App\Entity\Policy;
use App\Entity\User;
use App\Repository\PolicyRepository;
use App\Service\PermissionCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RequestStack;

class ClaimCrudController extends BaseCrudController
{
    public function __construct(
        PermissionCheckerService $permissionChecker,
        private RequestStack $requestStack,
        private PolicyRepository $policyRepository
    ) {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'claims';
    }

    public static function getEntityFqcn(): string
    {
        return Claim::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Claim')
            ->setEntityLabelInPlural('Claims')
            ->setDefaultSort(['claimDate' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        if ($this->permissionChecker->hasPermission($this->getModuleKey(), 'view')) {
            $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        }

        return $actions;
    }

    public function createEntity(string $entityFqcn)
    {
        $claim = new Claim();

        /** @var User|null $user */
        $user = $this->getUser();
        if ($user && $user->getAgency()) {
            $claim->setAgency($user->getAgency());
        }

        // Contextual Pre-selection: if clientId is passed, pre-select the policy
        $request = $this->requestStack->getCurrentRequest();
        $clientId = $request?->query->get('clientId');
        if ($clientId) {
            $policies = $this->policyRepository->findBy(['client' => $clientId]);
            if (count($policies) === 1) {
                $claim->setPolicy($policies[0]);
            }
        }

        return $claim;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var User $user */
        $user = $this->getUser();

        // LEFT COLUMN: CLAIM DETAILS
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Claim Details')
            ->setIcon('fa fa-file-medical')
            ->setHelp('Policy, type, and amount information');

        $policyField = AssociationField::new('policy', 'Policy')
            ->setRequired(true)
            ->setFormTypeOption('choice_label', static function (Policy $policy): string {
                $policyNumber = (string) ($policy->getPolicyNumber() ?? '');
                $clientName = (string) ($policy->getClient()?->getName() ?? '');

                return $clientName !== ''
                    ? sprintf('%s - %s', $policyNumber, $clientName)
                    : $policyNumber;
            })
            ->setColumns(12);

        $request = $this->requestStack->getCurrentRequest();
        $clientId = $request?->query->get('clientId');
        if ($clientId) {
            $policyField->setQueryBuilder(function (QueryBuilder $qb) use ($clientId) {
                return $qb->andWhere('entity.client = :clientId')
                          ->setParameter('clientId', $clientId);
            });
        }
        yield $policyField;

        yield ChoiceField::new('claimType', 'Claim Type')
            ->setChoices(array_flip(Claim::CLAIM_TYPES))
            ->renderAsBadges([
                Claim::TYPE_DEATH            => 'danger',
                Claim::TYPE_MATURITY         => 'success',
                Claim::TYPE_SURVIVAL_BENEFIT => 'info',
                Claim::TYPE_ACCIDENTAL_DEATH => 'danger',
            ])
            ->setRequired(true)
            ->setColumns(6);

        yield DateField::new('claimDate', 'Claim Date')
            ->setRequired(true)
            ->setColumns(6);

        yield MoneyField::new('claimedAmount', 'Claimed Amount')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setRequired(true)
            ->setColumns(6);

        yield TextField::new('claimantName', 'Claimant Name')
            ->setColumns(6)
            ->setHelp('Person who filed the claim (usually the nominee)');

        // RIGHT COLUMN: STATUS & SETTLEMENT
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Status & Settlement')
            ->setIcon('fa fa-clipboard-check');

        yield ChoiceField::new('status', 'Status')
            ->setChoices(array_flip(Claim::STATUSES))
            ->renderAsBadges([
                Claim::STATUS_INTIMATED           => 'secondary',
                Claim::STATUS_DOCUMENTS_SUBMITTED => 'info',
                Claim::STATUS_UNDER_PROCESS       => 'warning',
                Claim::STATUS_SETTLED             => 'success',
                Claim::STATUS_REPUDIATED          => 'danger',
            ])
            ->setRequired(true)
            ->setColumns(12);

        yield MoneyField::new('settledAmount', 'Settled Amount')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setColumns(6)
            ->setHelp('Actual amount settled by LIC. May differ from claimed.');

        yield DateField::new('settlementDate', 'Settlement Date')
            ->setColumns(6)
            ->setHelp('Date LIC credited the claim amount');

        yield TextareaField::new('notes', 'Notes')
            ->setColumns(12)
            ->hideOnIndex()
            ->setHelp('Agent notes on documents submitted, pending items, etc.');

        // AUDIT INFO
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

        // SYSTEM FIELDS
        yield FormField::addFieldset('System Metadata')->setIcon('fa fa-database');

        $agencyField = AssociationField::new('agency', 'Agency')
            ->setColumns(12);

        if ($user->isAdministrator()) {
            yield $agencyField
                ->setRequired(true)
                ->setHelp('Super Admin Only: Assign to a specific agency');
        } else {
            yield $agencyField
                ->hideOnIndex()
                ->setDisabled(true);
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Claim) {
            /** @var User $user */
            $user = $this->getUser();

            // Auto-set agency for non-admin users
            if ($user && $user->getAgency() && $entityInstance->getAgency() === null) {
                $entityInstance->setAgency($user->getAgency());
            }

            // Apply policy status update logic
            $this->applyPolicyStatusUpdate($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Claim) {
            // Apply policy status update logic
            $this->applyPolicyStatusUpdate($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Business Logic: Automatically update Policy status based on claim type and status.
     *
     * - DEATH or ACCIDENTAL_DEATH claim → Policy status = DEATH_CLAIM
     * - MATURITY claim + status = SETTLED → Policy status = MATURED
     */
    private function applyPolicyStatusUpdate(Claim $claim): void
    {
        $policy = $claim->getPolicy();
        if (!$policy) {
            return;
        }

        $claimType = $claim->getClaimType();
        $claimStatus = $claim->getStatus();

        // Death or Accidental Death → always set policy to DEATH_CLAIM
        if (in_array($claimType, [Claim::TYPE_DEATH, Claim::TYPE_ACCIDENTAL_DEATH], true)) {
            $policy->setStatus('DEATH_CLAIM');
        }

        // Maturity claim that is settled → set policy to MATURED
        if ($claimType === Claim::TYPE_MATURITY && $claimStatus === Claim::STATUS_SETTLED) {
            $policy->setStatus('MATURED');
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('claimType')
            ->add('status')
            ->add('policy')
            ->add('agency');
    }
}
