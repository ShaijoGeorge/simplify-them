<?php

namespace App\Controller\Admin;

use App\Entity\LicPlan;
use App\Entity\Policy;
use App\Entity\PolicyStatusLog;
use App\Entity\PremiumReceipt;
use App\Entity\User;
use App\Repository\SaRebateRepository;
use App\Service\MaturityProjectionService;
use App\Service\PremiumValidationService;
use App\Service\PermissionCheckerService;
use App\Service\WhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PolicyCrudController extends BaseCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MaturityProjectionService $maturityProjectionService,
        private PremiumValidationService $premiumValidationService,
        private SaRebateRepository $saRebateRepository,
        private WhatsAppService $whatsAppService,
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

        // Custom action: Upload Document (links to PolicyDocumentCrudController)
        $uploadDocAction = Action::new('uploadDocument', 'Upload Document', 'fa fa-file-upload')
            ->linkToUrl(function (Policy $policy): string {
                return $this->container->get('router')->generate('admin', [
                    'crudController' => PolicyDocumentCrudController::class,
                    'crudAction' => 'new',
                    'policyId' => $policy->getId(),
                ]);
            })
            ->setCssClass('btn btn-sm btn-outline-primary');

        $actions->add(Crud::PAGE_DETAIL, $uploadDocAction);
        $actions->add(Crud::PAGE_EDIT, $uploadDocAction);

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

        // Annual premium (can be entered directly or auto-derived from modal premium)
        yield MoneyField::new('annualPremium', 'Annual Premium')
            ->setCurrency('INR')
            ->setColumns(4)
            ->setHelp('Enter annual premium or let it auto-calculate from modal premium')
            ->hideOnIndex()
            ->setStoredAsCents(false);

        // Modal premium (can be entered directly or auto-derived from annual premium)
        yield MoneyField::new('basicPremium', 'Modal Premium')
            ->setCurrency('INR')
            ->setColumns(4)
            ->setHelp('Enter modal premium or let it auto-calculate from annual premium')
            ->hideOnIndex()
            ->setStoredAsCents(false);

        yield NumberField::new('modalRebate', 'Rebate Factor')
            ->setColumns(4)
            ->setDisabled(true)
            ->hideOnIndex()
            ->setNumDecimals(4)
            ->setHelp('HLY=0.51, QLY=0.26, MLY=0.0875');

        yield MoneyField::new('saRebateAmount', 'SA Rebate')
            ->setCurrency('INR')
            ->setColumns(4)
            ->setDisabled(true)
            ->hideOnIndex()
            ->setStoredAsCents(false)
            ->setHelp('Auto-calculated: High SA rebate deducted from modal premium');

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

        // WhatsApp column (index page only)
        if ($pageName === Crud::PAGE_INDEX) {
            yield TextField::new('whatsappUrl', 'WhatsApp')
                ->setTemplatePath('Admin/policy/whatsapp_column.html.twig')
                ->setVirtual(true)
                ->formatValue(function ($value, Policy $entity) {
                    $client = $entity->getClient();
                    if (!$client || !$client->getMobile() || !$entity->getNextDueDate()) {
                        return null;
                    }
                    return $this->whatsAppService->generateWhatsAppUrl(
                        $client->getMobile(),
                        $client->getName(),
                        $entity->getPolicyNumber(),
                        $entity->getNextDueDate()->format('d-M-Y'),
                        $entity->getTotalPremium() ?? '0'
                    );
                })
                ->setSortable(false);
        }

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

            // Auto-calculate SA rebate
            $this->applySaRebate($entityInstance);

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

            // Auto-calculate SA rebate
            $this->applySaRebate($entityInstance);

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

    /**
     * Look up the SA rebate band and set saRebateAmount on the policy.
     */
    private function applySaRebate(Policy $policy): void
    {
        $sa = (float) $policy->getSumAssured();
        if ($sa <= 0) {
            $policy->setSaRebateAmount(null);
            return;
        }

        $band = $this->saRebateRepository->findRebateForSumAssured($sa);
        if (!$band) {
            $policy->setSaRebateAmount(null);
            return;
        }

        $rebatePerThousand = (float) $band->getRebatePerThousand();
        $rebateAmount = round(($sa / 1000) * $rebatePerThousand, 2);
        $policy->setSaRebateAmount((string) $rebateAmount);
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        $responseParameters = parent::configureResponseParameters($responseParameters);

        if (Crud::PAGE_DETAIL === $responseParameters->get('pageName')) {
            $entity = $responseParameters->get('entity');
            if ($entity) {
                $policy = $entity->getInstance();
                $projection = $this->maturityProjectionService->calculateProjection($policy);
                $responseParameters->set('maturity_projection', $projection);
            }
        }

        return $responseParameters;
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

    // AJAX: Cross-check entered premium against LIC premium table
    #[Route('/admin/api/premium-check', name: 'app_admin_premium_check', methods: ['GET'])]
    public function premiumCheck(Request $request): JsonResponse
    {
        $planId        = (int) $request->query->get('planId', 0);
        $entryAge      = (int) $request->query->get('entryAge', 0);
        $policyTerm    = (int) $request->query->get('policyTerm', 0);
        $sumAssured    = (float) $request->query->get('sumAssured', 0);
        $annualPremium = (float) $request->query->get('annualPremium', 0);

        if ($planId <= 0 || $entryAge <= 0 || $policyTerm <= 0 || $sumAssured <= 0 || $annualPremium <= 0) {
            return $this->json(['found' => false]);
        }

        $plan = $this->entityManager->getRepository(LicPlan::class)->find($planId);
        if (!$plan) {
            return $this->json(['found' => false]);
        }

        $result = $this->premiumValidationService->validatePremium(
            $plan,
            $entryAge,
            $policyTerm,
            $sumAssured,
            $annualPremium
        );

        if ($result === null) {
            return $this->json(['found' => false]);
        }

        return $this->json($result);
    }

    // AJAX: Return SA rebate for a given Sum Assured value
    #[Route('/admin/api/sa-rebate', name: 'app_admin_sa_rebate', methods: ['GET'])]
    public function getSaRebate(Request $request): JsonResponse
    {
        $sumAssured = (float) $request->query->get('sumAssured', 0);

        if ($sumAssured <= 0) {
            return $this->json(['rebatePerThousand' => 0, 'rebateAmount' => 0]);
        }

        $band = $this->saRebateRepository->findRebateForSumAssured($sumAssured);

        if (!$band) {
            return $this->json(['rebatePerThousand' => 0, 'rebateAmount' => 0]);
        }

        $rebatePerThousand = (float) $band->getRebatePerThousand();
        $rebateAmount = round(($sumAssured / 1000) * $rebatePerThousand, 2);

        return $this->json([
            'rebatePerThousand' => $rebatePerThousand,
            'rebateAmount' => $rebateAmount,
        ]);
    }

    #[Route('/admin/policy/{id}/initiate-revival', name: 'app_admin_policy_initiate_revival', methods: ['POST'])]
    public function initiateRevival(int $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('revival_' . $id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin');
        }

        $policy = $this->entityManager->getRepository(Policy::class)->find($id);

        if (!$policy || $policy->getStatus() !== 'LAPSED') {
            $this->addFlash('danger', 'Revival can only be initiated for LAPSED policies.');
            return $this->redirectToRoute('admin');
        }

        $oldStatus = $policy->getStatus();
        $policy->setStatus('REVIVAL_PENDING');
        $policy->setRevivalRequestDate(new \DateTime());

        // Log the status transition
        $log = new PolicyStatusLog();
        $log->setPolicy($policy);
        $log->setOldStatus($oldStatus);
        $log->setNewStatus('REVIVAL_PENDING');
        $log->setTriggeredBy('manual:revival');
        $log->setReason('Revival initiated by agent');
        $this->entityManager->persist($log);

        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'Revival initiated for policy %s. Please enter the arrears amount from LIC quote and confirm.',
            $policy->getPolicyNumber()
        ));

        return $this->redirect($this->container->get('router')->generate('admin', [
            'crudController' => self::class,
            'crudAction' => 'detail',
            'entityId' => $id,
        ]));
    }

    #[Route('/admin/policy/{id}/confirm-revival', name: 'app_admin_policy_confirm_revival', methods: ['POST'])]
    public function confirmRevival(int $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('revival_' . $id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin');
        }

        $policy = $this->entityManager->getRepository(Policy::class)->find($id);

        if (!$policy || $policy->getStatus() !== 'REVIVAL_PENDING') {
            $this->addFlash('danger', 'Revival can only be confirmed for policies with REVIVAL_PENDING status.');
            return $this->redirectToRoute('admin');
        }

        $arrearsAmount = (float) $request->request->get('revival_arrears_amount', 0);
        if ($arrearsAmount <= 0) {
            $this->addFlash('danger', 'Please enter a valid arrears amount from the LIC quote.');
            return $this->redirect($this->container->get('router')->generate('admin', [
                'crudController' => self::class,
                'crudAction' => 'detail',
                'entityId' => $id,
            ]));
        }

        $oldStatus = $policy->getStatus();

        // Update policy
        $policy->setStatus('IN_FORCE');
        $policy->setRevivalArrearsAmount((string) $arrearsAmount);
        $policy->setFup(null);
        $policy->setPaidUpSumAssured(null);

        // Create a special PremiumReceipt for the revival payment
        $receipt = new PremiumReceipt();
        $receipt->setPolicy($policy);
        $receipt->setAgency($policy->getAgency());
        $receipt->setReceiptNumber('RVL-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6)));
        $receipt->setAmount((string) $arrearsAmount);
        $receipt->setPaymentDate(new \DateTime());
        $receipt->setPaymentMode('REVIVAL');
        $this->entityManager->persist($receipt);

        // Log the status transition
        $log = new PolicyStatusLog();
        $log->setPolicy($policy);
        $log->setOldStatus($oldStatus);
        $log->setNewStatus('IN_FORCE');
        $log->setTriggeredBy('manual:revival');
        $log->setReason(sprintf('Revival confirmed. Arrears paid: ₹%s', number_format($arrearsAmount, 2)));
        $this->entityManager->persist($log);

        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'Policy %s has been revived successfully. Arrears receipt %s created.',
            $policy->getPolicyNumber(),
            $receipt->getReceiptNumber()
        ));

        return $this->redirect($this->container->get('router')->generate('admin', [
            'crudController' => self::class,
            'crudAction' => 'detail',
            'entityId' => $id,
        ]));
    }

    #[Route('/admin/policy/{id}/record-surrender', name: 'app_admin_policy_record_surrender', methods: ['POST'])]
    public function recordSurrender(int $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('surrender_' . $id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin');
        }

        $policy = $this->entityManager->getRepository(Policy::class)->find($id);

        if (!$policy || !in_array($policy->getStatus(), ['IN_FORCE', 'PAID_UP'], true)) {
            $this->addFlash('danger', 'Surrender can only be recorded for IN_FORCE or PAID_UP policies.');
            return $this->redirectToRoute('admin');
        }

        $surrenderValue = (float) $request->request->get('surrender_value', 0);
        if ($surrenderValue <= 0) {
            $this->addFlash('danger', 'Please enter a valid surrender value from LIC.');
            return $this->redirect($this->container->get('router')->generate('admin', [
                'crudController' => self::class,
                'crudAction' => 'detail',
                'entityId' => $id,
            ]));
        }

        $surrenderNotes = trim($request->request->get('surrender_notes', ''));
        $oldStatus = $policy->getStatus();

        // Update policy
        $policy->setStatus('SURRENDERED');
        $policy->setSurrenderDate(new \DateTime());
        $policy->setSurrenderValue((string) $surrenderValue);

        // Append surrender notes if provided
        if ($surrenderNotes !== '') {
            $existingNotes = $policy->getNotes();
            $stamp = (new \DateTime())->format('d-M-Y');
            $surrenderNote = "[Surrender {$stamp}] {$surrenderNotes}";
            $policy->setNotes($existingNotes ? $existingNotes . "\n" . $surrenderNote : $surrenderNote);
        }

        // Log the status transition
        $log = new PolicyStatusLog();
        $log->setPolicy($policy);
        $log->setOldStatus($oldStatus);
        $log->setNewStatus('SURRENDERED');
        $log->setTriggeredBy('manual:surrender');
        $log->setReason(sprintf(
            'Policy surrendered. Surrender value: ₹%s%s',
            number_format($surrenderValue, 2),
            $surrenderNotes !== '' ? '. Notes: ' . $surrenderNotes : ''
        ));
        $this->entityManager->persist($log);

        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'Policy %s has been surrendered. Surrender value: ₹%s',
            $policy->getPolicyNumber(),
            number_format($surrenderValue, 2)
        ));

        return $this->redirect($this->container->get('router')->generate('admin', [
            'crudController' => self::class,
            'crudAction' => 'detail',
            'entityId' => $id,
        ]));
    }

    #[Route('/admin/policy/{id}/surrender-acknowledgement', name: 'app_admin_policy_surrender_pdf', methods: ['GET'])]
    public function surrenderAcknowledgement(int $id): Response
    {
        $policy = $this->entityManager->getRepository(Policy::class)->find($id);

        if (!$policy || $policy->getStatus() !== 'SURRENDERED' || !$policy->getSurrenderValue()) {
            throw $this->createNotFoundException('Surrender record not found.');
        }

        $html = $this->renderView('Admin/policy/surrender_acknowledgement.html.twig', [
            'policy' => $policy,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Surrender_' . $policy->getPolicyNumber() . '.pdf"',
            ]
        );
    }
}
