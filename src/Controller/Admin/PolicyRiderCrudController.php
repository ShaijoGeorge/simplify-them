<?php

namespace App\Controller\Admin;

use App\Entity\PolicyRider;
use App\Entity\Policy;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\PermissionCheckerService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Standalone CRUD for policy riders.
 * Riders are primarily managed via the inline collection on the Policy detail page,
 * but this controller allows direct management and is also referenced by the
 * "Add Rider" button on the Policy detail template.
 */
class PolicyRiderCrudController extends BaseCrudController
{
    public function __construct(
        private \Doctrine\ORM\EntityManagerInterface $entityManager,
        PermissionCheckerService $permissionChecker
    ) {
        parent::__construct($permissionChecker);
    }

    public function createEntity(string $entityFqcn): PolicyRider
    {
        $rider = new PolicyRider();

        $request  = $this->getContext()->getRequest();
        $policyId = $request->query->get('policyId');

        if ($policyId) {
            $em     = $this->container->get('doctrine')->getManager();
            $policy = $em->getRepository(Policy::class)->find($policyId);
            if ($policy) {
                $rider->setPolicy($policy);
                // Pre-fill start date with policy DOC for convenience
                if ($policy->getCommencementDate()) {
                    $rider->setRiderStartDate(clone $policy->getCommencementDate());
                }
            }
        }

        return $rider;
    }

    protected function getModuleKey(): string
    {
        return 'policy_riders';
    }

    public static function getEntityFqcn(): string
    {
        return PolicyRider::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Policy Rider')
            ->setEntityLabelInPlural('Policy Riders')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['riderType', 'policy.policyNumber']);
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
        // LEFT COLUMN
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Rider Identity')
            ->setIcon('fa fa-shield-halved')
            ->setHelp('The type of add-on benefit and the policy it is attached to');

        yield AssociationField::new('policy', 'Base Policy')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('The policy this rider is attached to');

        yield ChoiceField::new('riderType', 'Rider Type')
            ->setChoices([
                'DAB - Double Accident Benefit' => PolicyRider::TYPE_DAB,
                'PWBP - Premium Waiver' => PolicyRider::TYPE_PWBP,
                'CI - Critical Illness' => PolicyRider::TYPE_CI,
                'Term Rider - Extra Term Cover' => PolicyRider::TYPE_TERM_RIDER,
            ])
            ->renderAsBadges([
                PolicyRider::TYPE_DAB => 'warning',
                PolicyRider::TYPE_PWBP => 'info',
                PolicyRider::TYPE_CI => 'danger',
                PolicyRider::TYPE_TERM_RIDER => 'primary',
            ])
            ->setColumns(6)
            ->setRequired(true);

        yield BooleanField::new('isActive', 'Active')
            ->setColumns(6)
            ->renderAsSwitch(false)
            ->setHelp('Deactivate a rider without deleting the historical record');

        // RIGHT COLUMN
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Financial Details')
            ->setIcon('fa fa-rupee-sign')
            ->setHelp('Benefit amounts and premium charged for this rider');

        yield MoneyField::new('riderSumAssured', 'Rider Sum Assured')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setColumns(6)
            ->setHelp('e.g. For DAB this equals the base SA - doubles on accidental death');

        yield MoneyField::new('riderPremium', 'Annual Rider Premium')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('Added to premium breakdown on receipts');

        // FULL WIDTH - Dates
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Validity Period')
            ->setIcon('fa fa-calendar-days')
            ->setHelp('Riders may expire before the base policy matures');

        yield DateField::new('riderStartDate', 'Start Date')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('Usually the same as the policy DOC');

        yield DateField::new('riderEndDate', 'End Date')
            ->setColumns(6)
            ->setHelp('Leave blank if rider runs for the full policy term');
    }
}