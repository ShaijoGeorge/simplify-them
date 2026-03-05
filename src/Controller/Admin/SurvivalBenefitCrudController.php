<?php

namespace App\Controller\Admin;

use App\Entity\SurvivalBenefit;
use App\Service\PermissionCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class SurvivalBenefitCrudController extends BaseCrudController
{
    public function __construct(
        PermissionCheckerService $permissionChecker,
        private AdminUrlGenerator $adminUrlGenerator
    ) {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'survival_benefit';
    }

    public static function getEntityFqcn(): string
    {
        return SurvivalBenefit::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Survival Benefit')
            ->setEntityLabelInPlural('Survival Benefits')
            ->setDefaultSort(['dueDate' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        $markCollected = Action::new('markCollected', 'Mark Collected', 'fa fa-check-circle')
            ->linkToCrudAction('markCollected')
            ->setCssClass('btn btn-success btn-sm')
            ->displayIf(static function (SurvivalBenefit $entity) {
                return $entity->getStatus() === SurvivalBenefit::STATUS_PENDING;
            });

        if ($this->permissionChecker->hasPermission($this->getModuleKey(), 'edit')) {
            $actions
                ->add(Crud::PAGE_INDEX, $markCollected)
                ->add(Crud::PAGE_DETAIL, $markCollected);
        }

        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        // LEFT COLUMN: BENEFIT DETAILS
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Benefit Details')
            ->setIcon('fa fa-hand-holding-dollar')
            ->setHelp('Policy and payout information');

        yield AssociationField::new('policy', 'Linked Policy')
            ->setRequired(true)
            ->setColumns(6);

        yield DateField::new('dueDate', 'Due Date')
            ->setColumns(6);

        yield MoneyField::new('amount', 'Payout Amount')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setColumns(6);

        yield NumberField::new('percentageOfSA', '% of Sum Assured')
            ->setNumDecimals(2)
            ->setColumns(6)
            ->setHelp('e.g. 20.00 = 20% of SA');

        // RIGHT COLUMN: STATUS & NOTES
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Collection Status')
            ->setIcon('fa fa-clipboard-check');

        yield ChoiceField::new('status', 'Status')
            ->setChoices(array_flip(SurvivalBenefit::STATUSES))
            ->renderAsBadges([
                SurvivalBenefit::STATUS_PENDING => 'warning',
                SurvivalBenefit::STATUS_COLLECTED => 'success',
                SurvivalBenefit::STATUS_MISSED => 'danger',
            ])
            ->setColumns(6);

        yield DateField::new('collectedDate', 'Collected Date')
            ->setColumns(6);

        yield TextareaField::new('notes', 'Notes')
            ->setColumns(12)
            ->hideOnIndex();

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
    }

    /**
     * Custom action: mark a PENDING benefit as COLLECTED.
     *
     * Works both from CRUD pages (where AdminContext has an entity)
     * and from the dashboard (where only the entityId query param exists).
     */
    public function markCollected(AdminContext $context, EntityManagerInterface $em): Response
    {
        $entityId = $context->getRequest()->query->get('entityId');
        $benefit = $em->getRepository(SurvivalBenefit::class)->find($entityId);

        if ($benefit && $benefit->getStatus() === SurvivalBenefit::STATUS_PENDING) {
            $benefit->setStatus(SurvivalBenefit::STATUS_COLLECTED);
            $benefit->setCollectedDate(new \DateTime());
            $em->flush();

            $this->addFlash('success', sprintf(
                'Survival Benefit for %s marked as COLLECTED.',
                $benefit->getPolicy()?->getPolicyNumber() ?? 'N/A'
            ));
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
