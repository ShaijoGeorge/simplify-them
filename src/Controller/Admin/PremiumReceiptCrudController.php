<?php

namespace App\Controller\Admin;

use App\Entity\CommissionRule;
use App\Entity\Policy;
use App\Entity\PremiumReceipt;
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

    public function __construct(PermissionCheckerService $permissionChecker)
    {
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
        // ── PANEL 1: Policy & Expected Premium ────────────────────────
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Policy & Expected Premium')
            ->setIcon('fa fa-file-invoice')
            ->setHelp('Policy link and the premium amount expected by LIC');

        yield TextField::new('receiptNumber', 'Receipt No')
            ->hideOnForm()
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

        yield MoneyField::new('basePremium', 'Base Premium')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setRequired(true)
            ->setColumns(6);

        yield MoneyField::new('licFineAmount', 'LIC Late Fine')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setHelp('Penalty imposed by LIC for late payment (if any)')
            ->setColumns(6);

        yield FormField::addColumn(6);

        // Client Collection (Phase 1)

        yield FormField::addFieldset('1. Client Collection')
            ->setIcon('fa fa-hand-holding-usd')
            ->setHelp('What the agent collected from the client');

        yield MoneyField::new('collectedAmount', 'Amount Collected')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setColumns(4);

        yield DateField::new('collectedDate', 'Collection Date')
            ->setColumns(4);

        yield ChoiceField::new('collectionMethod', 'Collection Method')
            ->setChoices([
                'Cash' => 'CASH',
                'UPI/Online' => 'ONLINE',
                'Cheque' => 'CHEQUE',
            ])
            ->renderAsBadges()
            ->setColumns(4);

        // LIC Payment (Phase 2)

        yield FormField::addFieldset('2. LIC Payment')
            ->setIcon('fa fa-university')
            ->setHelp('What the agent paid to LIC');

        yield MoneyField::new('paidToLicAmount', 'Amount Paid to LIC')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setColumns(4);

        yield DateField::new('paidToLicDate', 'LIC Payment Date')
            ->setColumns(4);

        yield ChoiceField::new('paymentChannel', 'Payment Channel')
            ->setChoices([
                'Cash' => 'CASH',
                'UPI/Online' => 'ONLINE',
                'Cheque' => 'CHEQUE',
            ])
            ->renderAsBadges()
            ->setColumns(4);

        // Status & Commission
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Status & Commission')
            ->setIcon('fa fa-calculator');

        yield ChoiceField::new('status', 'Workflow Status')
            ->setChoices([
                'Pending' => PremiumReceipt::STATUS_PENDING,
                'Collected, Not Paid to LIC' => PremiumReceipt::STATUS_COLLECTED_ONLY,
                'Paid to LIC, Not Collected' => PremiumReceipt::STATUS_PAID_ONLY,
                'Completed' => PremiumReceipt::STATUS_COMPLETED,
            ])
            ->renderAsBadges([
                PremiumReceipt::STATUS_PENDING => 'warning',
                PremiumReceipt::STATUS_COLLECTED_ONLY => 'info',
                PremiumReceipt::STATUS_PAID_ONLY => 'primary',
                PremiumReceipt::STATUS_COMPLETED => 'success',
            ])
            ->setDisabled(true)
            ->setHelp('Auto-derived from collection & payment data')
            ->setColumns(12);

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

        // Metadata
        yield FormField::addColumn(6);

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

        // Agency Fallback
        if ($user && $user->getAgency() && !$entityInstance->getAgency()) {
            $entityInstance->setAgency($user->getAgency());
        }

        // Generate Receipt Number
        if (!$entityInstance->getReceiptNumber()) {
            $entityInstance->setReceiptNumber(
                'REC-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6))
            );
        }

        // Calculate Commission (on paidToLicAmount)
        $this->calculateCommission($entityManager, $entityInstance);

        // Adjust Client Wallet
        $this->adjustClientWallet($entityManager, $entityInstance, null);

        // Update Policy Due Date (ONLY ON CREATE)
        $this->advancePolicyDueDate($entityManager, $entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
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

        // We need the OLD values to reverse the previous wallet delta.
        // Doctrine's UnitOfWork holds the original data.
        $uow = $entityManager->getUnitOfWork();
        $originalData = $uow->getOriginalEntityData($entityInstance);

        // Recalculate Commission
        $this->calculateCommission($entityManager, $entityInstance);

        // Adjust Client Wallet (reverse old, apply new)
        $this->adjustClientWallet($entityManager, $entityInstance, $originalData);

        parent::updateEntity($entityManager, $entityInstance);
    }

    // ── WALLET ADJUSTMENT ─────────────────────────────────────────────

    /**
     * Adjust the client's wallet balance based on the difference between
     * collected and paid-to-LIC amounts.
     *
     * On CREATE: $originalData is null – just apply the new delta.
     * On UPDATE: reverse old delta, then apply new delta.
     */
    private function adjustClientWallet(
        EntityManagerInterface $em,
        PremiumReceipt $receipt,
        ?array $originalData
    ): void {
        $client = $receipt->getPolicy()?->getClient();
        if (!$client) {
            return;
        }

        $newCollected = (float) ($receipt->getCollectedAmount() ?? 0);
        $newPaid      = (float) ($receipt->getPaidToLicAmount() ?? 0);

        // Only apply wallet logic when BOTH sides are filled (receipt is COMPLETED)
        if ($newCollected <= 0 || $newPaid <= 0) {
            // If updating and was previously completed, reverse old delta
            if ($originalData !== null) {
                $oldCollected = (float) ($originalData['collectedAmount'] ?? 0);
                $oldPaid      = (float) ($originalData['paidToLicAmount'] ?? 0);
                if ($oldCollected > 0 && $oldPaid > 0) {
                    $oldDelta = $oldCollected - $oldPaid;
                    $client->adjustWalletBalance(-$oldDelta);
                    $em->persist($client);
                }
            }
            return;
        }

        $newDelta = $newCollected - $newPaid;

        if ($originalData !== null) {
            // Reverse old delta first
            $oldCollected = (float) ($originalData['collectedAmount'] ?? 0);
            $oldPaid      = (float) ($originalData['paidToLicAmount'] ?? 0);
            if ($oldCollected > 0 && $oldPaid > 0) {
                $oldDelta = $oldCollected - $oldPaid;
                $client->adjustWalletBalance(-$oldDelta);
            }
        }

        // Apply new delta
        $client->adjustWalletBalance($newDelta);
        $em->persist($client);
    }

    // ── COMMISSION CALCULATION ────────────────────────────────────────
    // Priority order:
    //   1. Single-premium override  → 2 % flat (no CommissionRule lookup needed)
    //   2. CommissionRule DB lookup → rate defined per plan / year / term
    //   3. Fallback                 → 0 % (no matching rule found)
    //
    // Commission is now calculated on paidToLicAmount (the LIC-facing premium).
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

        // Use paidToLicAmount if available, otherwise basePremium
        $commissionBase = $receipt->getPaidToLicAmount() ?? $receipt->getBasePremium();
        if (!$commissionBase) {
            return;
        }

        $grossCommission = 0.0;

        // 1. Single-premium override
        if ($plan->isSinglePremium() || $policy->isSinglePremiumPolicy()) {
            $grossCommission = ((float) $commissionBase * self::SINGLE_PREMIUM_COMMISSION_RATE) / 100;
        } else {
            // 2. Standard CommissionRule lookup
            $doc = $policy->getCommencementDate();
            $payDate = $receipt->getPaidToLicDate() ?? $receipt->getCollectedDate() ?? new \DateTime();

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
                $grossCommission = ((float) $commissionBase * (float) $rule->getCommissionRate()) / 100;
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
