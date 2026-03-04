<?php

namespace App\Controller\Admin;

use App\Entity\LicPlan;
use App\Entity\Policy;
use App\Entity\User;
use App\Service\PermissionCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PolicyCrudController extends BaseCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        PermissionCheckerService $permissionChecker
    ) {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'policies';
    }

    public static function getEntityFqcn(): string
    {
        return Policy::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Policy')
            ->setEntityLabelInPlural('Policies')
            ->overrideTemplate('crud/detail', 'Admin/policy/detail.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        if ($this->permissionChecker->hasPermission($this->getModuleKey(), 'view')) {
            $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        }

        return $actions;
    }

    public function configureAssets(\EasyCorp\Bundle\EasyAdminBundle\Config\Assets $assets): \EasyCorp\Bundle\EasyAdminBundle\Config\Assets
    {
        return parent::configureAssets($assets)
            ->addJsFile('assets/js/admin/policy_dates.js')
            ->addJsFile('assets/js/admin/conditional_la_fields.js');
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
            ->setRequired(true)
            ->setHelp('Selecting a Single Premium plan will auto-lock the Payment Mode.');

        yield DateField::new('commencementDate', 'Date of Commencement (DOC)')
            ->setColumns(12)
            ->hideOnIndex();


        yield FormField::addFieldset('Terms & Conditions')
            ->setIcon('fa fa-sliders-h');

        yield NumberField::new('policyTerm', 'Policy Term (Years)')
            ->setColumns(4)
            ->hideOnIndex();

        yield NumberField::new('premiumPayingTerm', 'PPT (Years)')
            ->setColumns(4)
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
            ->setColumns(4);

        yield FormField::addFieldset('Valuation & Premiums')
            ->setIcon('fa fa-rupee-sign')
            ->setHelp('Financial values associated with this policy');

        yield MoneyField::new('sumAssured', 'Sum Assured')
            ->setCurrency('INR')
            ->setColumns(12)
            ->hideOnIndex()
            ->setStoredAsCents(false);

        // Annual (tabular) premium — the primary input
        yield MoneyField::new('annualPremium', 'Annual Premium')
            ->setCurrency('INR')
            ->setColumns(4)
            ->setHelp('Tabular annual premium from LIC plan table')
            ->hideOnIndex()
            ->setStoredAsCents(false);

        // Group premiums visually
        yield MoneyField::new('basicPremium', 'Modal Premium')
            ->setCurrency('INR')
            ->setColumns(4)
            ->setHelp('Auto-calculated: Annual × rebate factor')
            ->hideOnIndex()
            ->setStoredAsCents(false);

        yield NumberField::new('modalRebate', 'Rebate Factor')
            ->setColumns(4)
            ->setDisabled(true)
            ->hideOnIndex()
            ->setNumDecimals(4)
            ->setHelp('HLY=0.51, QLY=0.26, MLY=0.0875');

        yield MoneyField::new('gst', 'GST')
            ->setCurrency('INR')
            ->setColumns(4)
            ->setDisabled(true)
            ->setStoredAsCents(false);

        yield MoneyField::new('totalPremium', 'Total')
            ->setCurrency('INR')
            ->setColumns(4)
            ->setDisabled(true)
            ->setStoredAsCents(false)
            ->setHelp('Auto-calculated (Modal + GST)');


        // RIGHT COLUMN: FINANCIALS & STATUS
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Status & Tracking')
            ->setIcon('fa fa-clock');

        yield ChoiceField::new('status')
            ->setChoices([
                'In Force' => 'IN_FORCE',
                'Lapsed' => 'LAPSED',
                'Matured' => 'MATURED',
                'Paid Up' => 'PAID_UP',
                'Surrendered' => 'SURRENDERED',
                'Death Claim' => 'DEATH_CLAIM',
                'Revival Pending' => 'REVIVAL_PENDING'
            ])
            ->renderAsBadges() // Nice color coding
            ->setColumns(12);

        yield DateField::new('nextDueDate', 'Next Premium Due')
            ->setColumns(4);

        yield DateField::new('fup', 'FUP Date')
            ->setColumns(4);

        yield DateField::new('maturityDate', 'Maturity Date')
            ->setColumns(4)
            ->hideOnIndex();

        // CONDITIONAL LIFE ASSURED SECTION
        yield FormField::addFieldset('Life Assured Details')
            ->setIcon('fa fa-user-shield')
            ->setHelp('Fill ONLY if policy is for a minor or a different life assured.');

        // This is just a UI toggle, it doesn't save to the database (mapped: false)
        yield BooleanField::new('isDifferentLifeAssured', 'Life Assured is different from Client')
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('row_attr', ['class' => 'la-toggle-wrapper'])
            ->hideOnIndex();

        yield TextField::new('lifeAssuredName', 'Life Assured Name')
            ->setColumns(12)
            ->hideOnIndex()
            ->setFormTypeOption('row_attr', ['class' => 'conditional-la-field']);

        yield DateField::new('lifeAssuredDob', 'Life Assured DOB')
            ->setColumns(6)
            ->hideOnIndex()
            ->setFormTypeOption('row_attr', ['class' => 'conditional-la-field']);

        yield ChoiceField::new('lifeAssuredGender', 'Life Assured Gender')
            ->setChoices([
                'Male' => 'MALE', 
                'Female' => 'FEMALE'
            ])
            ->setColumns(6)
            ->hideOnIndex()
            ->setFormTypeOption('row_attr', ['class' => 'conditional-la-field']);

        // ADDITIONAL DETAILS SECTION
        yield FormField::addFieldset('Additional Policy Details')
            ->setIcon('fa fa-folder-open')
            ->setHelp('LIC internal tracking and supplementary information.');

        yield TextField::new('licBondNumber', 'LIC Bond Number')
            ->hideOnIndex();

        yield TextField::new('licBranch', 'LIC Branch')
            ->hideOnIndex();

        yield TextareaField::new('notes', 'Notes')
            ->hideOnIndex();

        // META DATA
        yield FormField::addFieldset('System Metadata')->setIcon('fa fa-database');

        $agencyField = AssociationField::new('agency', 'Agency')
            ->setColumns(12);

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

            // Auto-calculate Paid-Up SA when status is set to PAID_UP
            if ($entityInstance->getStatus() === 'PAID_UP') {
                $entityInstance->calculatePaidUpSumAssured();
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

            // Auto-calculate Paid-Up SA when status is set to PAID_UP
            if ($entityInstance->getStatus() === 'PAID_UP') {
                $entityInstance->calculatePaidUpSumAssured();
            } elseif ($entityInstance->getStatus() !== 'PAID_UP') {
                // Clear stored value if status moves away from PAID_UP
                $entityInstance->setPaidUpSumAssured(null);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('nextDueDate')
            ->add('client')
            ->add('agency');
    }

    // AJAX: Return plan flags for the JS premium-behaviour engine
    #[Route('/admin/api/lic-plan/{id}/flags', name: 'app_admin_lic_plan_flags', methods: ['GET'])]
    public function getLicPlanFlags(int $id): JsonResponse
    {
        $plan = $this->entityManager->getRepository(LicPlan::class)->find($id);

        if (!$plan) {
            return $this->json(['error' => 'Plan not found'], 404);
        }

        return $this->json([
            'isSinglePremium'  => $plan->isSinglePremium(),
            'isLimitedPremium' => $plan->isLimitedPremium(),
            'planTypeName'     => $plan->getPlanType()?->getName() ?? '',
        ]);
    }
}
