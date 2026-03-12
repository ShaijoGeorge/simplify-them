<?php

namespace App\Controller\Admin;

use App\Entity\CommissionRule;
use App\Entity\Policy;
use App\Entity\PremiumReceipt;
use App\Service\LedgerService;
use App\Service\PermissionCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

class PremiumReceiptCrudController extends BaseCrudController
{
    // Commission rate applied to all single-premium policies (flat, no CommissionRule needed).
    private const SINGLE_PREMIUM_COMMISSION_RATE = 2.0;

    public function __construct(
        PermissionCheckerService $permissionChecker,
        private LedgerService $ledgerService,
    ) {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'premium_collection';
    }

    public static function getEntityFqcn(): string
    {
        return PremiumReceipt::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Premium Collection')
            ->setEntityLabelInPlural('Premium Collections');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        $downloadPdf = Action::new('downloadPDF', 'Download Receipt', 'fa fa-file-pdf')
            ->linkToCrudAction('generatePdf');

        if ($this->permissionChecker->hasPermission($this->getModuleKey(), 'view')) {
            $actions
                ->add(Crud::PAGE_INDEX, $downloadPdf)
                ->add(Crud::PAGE_DETAIL, $downloadPdf)
                ->add(Crud::PAGE_INDEX, Action::DETAIL);
        }

        return $actions;
    }

    public function createEntity(string $entityFqcn)
    {
        $premiumReceipt = new PremiumReceipt();
        $user = $this->getUser();

        if ($user && $user->getAgency()) {
            $premiumReceipt->setAgency($user->getAgency());
        }

        return $premiumReceipt;
    }

    public function configureFields(string $pageName): iterable
    {
        // LEFT COLUMN: TRANSACTION INFO
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Transaction Details')
            ->setIcon('fa fa-file-invoice')
            ->setHelp('Policy and date information');

        yield TextField::new('receiptNumber', 'Receipt No')
            ->hideOnForm() // Generated automatically
            ->setColumns(12);

        yield AssociationField::new('policy', 'Linked Policy')
            ->setRequired(true)
            ->setFormTypeOption('choice_label', static function (Policy $policy): string {
                $policyNumber = (string) ($policy->getPolicyNumber() ?? '');
                $clientName = (string) ($policy->getClient()?->getName() ?? '');

                return $clientName !== ''
                    ? sprintf('%s - %s', $policyNumber, $clientName)
                    : $policyNumber;
            })
            ->setColumns(12);

        yield DateField::new('paymentDate', 'Payment Date')
            ->setColumns(12);

        // RIGHT COLUMN: PAYMENT SPECS
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Payment Specification')
            ->setIcon('fa fa-money-bill-wave');

        yield MoneyField::new('amount', 'Amount Received')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setColumns(12);

        yield ChoiceField::new('paymentMode', 'Payment Mode')
            ->setChoices([
                'Cash' => 'CASH',
                'UPI/Online' => 'ONLINE',
                'Cheque' => 'CHEQUE',
            ])
            ->renderAsBadges()
            ->setColumns(12);

        // CLIENT COLLECTION TRACKING
        yield FormField::addFieldset('Client Collection')
            ->setIcon('fa fa-hand-holding-usd')
            ->setHelp('Track when and how much was collected from the client');

        yield MoneyField::new('collectedFromClient', 'Collected from Client')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setColumns(12)
            ->setHelp('Actual amount collected from client (may differ from LIC payment)');

        yield DateField::new('collectionDate', 'Collection Date')
            ->setColumns(12)
            ->setHelp('When the agent collected money from the client');

        // COMMISSION BREAKDOWN (read-only)
        yield FormField::addFieldset('Commission & TDS')->setIcon('fa fa-calculator');

        yield MoneyField::new('grossCommission', 'Gross Commission')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setDisabled(true);

        yield MoneyField::new('tdsOnCommission', 'TDS Deducted')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setDisabled(true);

        yield MoneyField::new('netCommission', 'Net Commission')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setDisabled(true);

        // META DATA
        yield FormField::addFieldset('System Metadata')->setIcon('fa fa-database');

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

        if (!$entityInstance instanceof PremiumReceipt) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $user = $this->getUser();

        // Agency Fallbak
        if ($user && $user->getAgency() && !$entityInstance->getAgency()) {
            $entityInstance->setAgency($user->getAgency());
        }

        // Generate Receipt Number (Simple Random for now)
        if (!$entityInstance->getReceiptNumber()) {
            $entityInstance->setReceiptNumber(
                'REC-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6))
            );
        }

        // Calculate Commission (Shared Logic)
        $this->calculateCommission($entityManager, $entityInstance);

        // Update Policy Due Date (ONLY ON CREATE)
        $this->advancePolicyDueDate($entityManager, $entityInstance);

        parent::persistEntity($entityManager, $entityInstance);

        // Auto-create ledger entries (PAID_TO_LIC + optional COLLECTION)
        $this->ledgerService->recordPaidToLic($entityInstance);
        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof PremiumReceipt) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        $user = $this->getUser();

        // Agency Fallback
        if ($user && $user->getAgency() && !$entityInstance->getAgency()) {
            $entityInstance->setAgency($user->getAgency());
        }

        // Recalculate Commission (In case Amount changed)
        $this->calculateCommission($entityManager, $entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    // COMMISSION CALCULATION
    // Priority order:
    //   1. Single-premium override  → 2 % flat (no CommissionRule lookup needed)
    //   2. CommissionRule DB lookup → rate defined per plan / year / term
    //   3. Fallback                 → 0 % (no matching rule found)
    private function calculateCommission(EntityManagerInterface $em, PremiumReceipt $receipt): void
    {
        $policy = $receipt->getPolicy();
        if (!$policy) {
            return;
        }

        $plan = $policy->getLicPlan();
        if (!$plan) {
            return;
        }

        $grossCommission = 0.0;

        // 1. Single-premium override
        // Check BOTH the LicPlan flag AND the policy's premiumMode so the override
        // fires regardless of which mechanism identified the policy as single-premium.
        if ($plan->isSinglePremium() || $policy->isSinglePremiumPolicy()) {
            $grossCommission = ((float) $receipt->getAmount() * self::SINGLE_PREMIUM_COMMISSION_RATE) / 100;
        } else {
            // 2. Standard CommissionRule lookup
            $doc = $policy->getCommencementDate();
            $payDate = $receipt->getPaymentDate() ?? new \DateTime();

            $diff = $doc->diff($payDate);
            $policyYear = $diff->y + 1;
            $term = $policy->getPolicyTerm();

            // Find Rule
            $rule = $em->getRepository(CommissionRule::class)->createQueryBuilder('c')
                ->where('c.licPlan = :plan')
                ->andWhere('c.policyYearFrom <= :year')
                ->andWhere('c.policyYearTo >= :year')
                ->andWhere('c.minTerm <= :term')
                ->andWhere('c.maxTerm >= :term')
                ->setParameter('plan', $plan)
                ->setParameter('year', $policyYear)
                ->setParameter('term', $term)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($rule) {
                $grossCommission = ((float) $receipt->getAmount() * (float) $rule->getCommissionRate()) / 100;
            }
            // else: no rule found — stays 0
        }

        // 3. Compute TDS (Section 194D): 5 % with PAN, 20 % without
        $agency = $receipt->getAgency();
        $tdsRate = ($agency && $agency->hasPan()) ? 5.0 : 20.0;
        $tds = ($grossCommission * $tdsRate) / 100;
        $net = $grossCommission - $tds;

        $receipt->setGrossCommission((string) round($grossCommission, 2));
        $receipt->setTdsOnCommission((string) round($tds, 2));
        $receipt->setNetCommission((string) round($net, 2));
    }

    // Advance the policy's next due date by one premium interval after payment.
    private function advancePolicyDueDate(EntityManagerInterface $em, PremiumReceipt $receipt): void
    {
        $policy = $receipt->getPolicy();

        // Single-premium policies have no recurring due date — skip.
        if (!$policy || !$policy->getNextDueDate() || $policy->isSinglePremiumPolicy()) {
            return;
        }

        $newDueDate = clone $policy->getNextDueDate();
        $mode = strtoupper($policy->getPremiumMode());

        match ($mode) {
                'YLY', 'YEARLY' => $newDueDate->modify('+1 year'),
                'HLY', 'HALF-YEARLY' => $newDueDate->modify('+6 months'),
                'QLY', 'QUARTERLY' => $newDueDate->modify('+3 months'),
            'MLY', 'MONTHLY', 'NACH' => $newDueDate->modify('+1 month'),
                default => null,
        };

        $policy->setNextDueDate($newDueDate);
        $em->persist($policy);
    }

    // PDF Receipt Generator
    public function generatePdf(AdminContext $context, EntityManagerInterface $entityManager)
    {
        $receiptId = $context->getRequest()->query->get('entityId');
        $receipt = $entityManager->getRepository(PremiumReceipt::class)->find($receiptId);

        if (!$receipt) {
            throw $this->createNotFoundException('Receipt not found');
        }

        $html = $this->renderView(
            'Admin/premium_receipt/receipt.html.twig',
            ['receipt' => $receipt]
        );

        // Convert HTML to PDF
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();

        // Output the PDF to browser
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Receipt_'.$receipt->getReceiptNumber().'.pdf"',
            ]
        );
    }
}
