<?php

namespace App\Controller\Admin;

use App\Entity\Nominee;
use App\Entity\Policy;
use App\Service\PermissionCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class NomineeCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'nominees';
    }

    public static function getEntityFqcn(): string
    {
        return Nominee::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Nominee')
            ->setEntityLabelInPlural('Nominees')
            ->setDefaultSort(['id' => 'DESC']);
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

        yield FormField::addFieldset('Nominee Identity')
            ->setIcon('fa fa-user-shield')
            ->setHelp('Personal details of the nominee as per policy documents');

        yield AssociationField::new('policy', 'Linked Policy')
            ->setColumns(12)
            ->setRequired(true)
            ->setHelp('The policy this nominee is attached to');

        yield TextField::new('name', 'Full Name')
            ->setColumns(12)
            ->setRequired(true);

        yield ChoiceField::new('relationship', 'Relationship')
            ->setChoices([
                'Spouse' => 'SPOUSE',
                'Son' => 'SON',
                'Daughter' => 'DAUGHTER',
                'Father' => 'FATHER',
                'Mother' => 'MOTHER',
                'Brother' => 'BROTHER',
                'Sister' => 'SISTER',
                'Other' => 'OTHER',
            ])
            ->renderAsBadges()
            ->setColumns(12)
            ->setRequired(true);

        yield DateField::new('dob', 'Date of Birth')
            ->setColumns(6)
            ->setHelp('Required if nominee is a minor (under 18)');

        yield NumberField::new('sharePercentage', 'Share %')
            ->setColumns(6)
            ->setNumDecimals(2)
            ->setHelp('All nominees combined must total 100%')
            ->setRequired(true);

        // RIGHT COLUMN
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Guardian & Contact')
            ->setIcon('fa fa-address-card')
            ->setHelp('Guardian details are mandatory when nominee is a minor');

        yield TextField::new('guardianName', 'Guardian Name')
            ->setColumns(12)
            ->setHelp('Mandatory if nominee is under 18 years of age');

        yield TelephoneField::new('mobile', 'Mobile (for claim)')
            ->setColumns(12)
            ->setFormTypeOption('attr', [
                'data-intl-phone'     => 'true',
                'data-default-country' => 'in',
            ])
            ->setHelp('Contact number used during claim intimation');

        yield FormField::addFieldset('KYC Details')
            ->setIcon('fa fa-id-card')
            ->setHelp('Aadhar is used for identity verification during claim settlement');

        yield TextField::new('aadhar', 'Aadhar Number')
            ->setColumns(12)
            ->setHelp('12-digit Aadhar number (optional but recommended)');
    }

    // Auto-link the nominee to its policy when creating via the "Add Nominee"
    // button on the Policy detail page (query param: policyId).
    public function createEntity(string $entityFqcn): Nominee
    {
        $nominee = new Nominee();

        $request   = $this->getContext()->getRequest();
        $policyId  = $request->query->get('policyId');

        if ($policyId) {
            $em     = $this->container->get('doctrine')->getManager();
            $policy = $em->getRepository(Policy::class)->find($policyId);
            if ($policy) {
                $nominee->setPolicy($policy);
            }
        }

        return $nominee;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->validateSharePercentage($entityManager, $entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->validateSharePercentage($entityManager, $entityInstance, $entityInstance->getId());
        parent::updateEntity($entityManager, $entityInstance);
    }

    // Ensures the total share across all nominees for the policy does not exceed 100%.
    private function validateSharePercentage(
        EntityManagerInterface $em,
        Nominee $nominee,
        ?int $excludeId = null
    ): void {
        $policy = $nominee->getPolicy();
        if (!$policy) {
            return;
        }

        $existing = $em->getRepository(Nominee::class)->getTotalShareForPolicy(
            $policy->getId(),
            $excludeId
        );

        $newShare = (float) $nominee->getSharePercentage();
        $total    = $existing + $newShare;

        if ($total > 100) {
            throw new BadRequestHttpException(
                sprintf(
                    'Share percentage exceeds 100%%: existing nominees hold %.2f%%, you are adding %.2f%% (total would be %.2f%%).',
                    $existing,
                    $newShare,
                    $total
                )
            );
        }
    }
}