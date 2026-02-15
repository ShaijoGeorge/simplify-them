<?php

namespace App\Controller\Admin;

use App\Entity\CommissionRule;
use App\Entity\PremiumReceipt;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

class PremiumReceiptCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PremiumReceipt::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $downloadPdf = Action::new('downloadPDF', 'Download Receipt', 'fa fa-file-pdf')
            ->linkToCrudAction('generatePdf');

        return $actions
            ->add(Crud::PAGE_INDEX, $downloadPdf)
            ->add(Crud::PAGE_DETAIL, $downloadPdf)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
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
            ->setColumns(12);

        yield DateField::new('paymentDate', 'Payment Date')
            ->setColumns(12);

        // RIGHT COLUMN: PAYMENT SPECS
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Payment Specification')
            ->setIcon('fa fa-money-bill-wave');

        yield MoneyField::new('amount', 'Amount Received')
            ->setCurrency('INR')
            ->setColumns(12);

        yield ChoiceField::new('paymentMode', 'Payment Mode')
            ->setChoices([
                'Cash' => 'CASH',
                'UPI/Online' => 'ONLINE',
                'Cheque' => 'CHEQUE',
            ])
            ->renderAsBadges()
            ->setColumns(12);

        // HIDDEN FIELDS
        yield MoneyField::new('commissionEarned', 'Commission Earned')
            ->setCurrency('INR')
            ->hideOnForm();
        
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
            $entityInstance->setReceiptNumber('REC-' . strtoupper(uniqid()));
        }

        // Calculate Commission (Shared Logic)
        $this->calculateCommission($entityManager, $entityInstance);

        // Update Policy Due Date (ONLY ON CREATE)
        $this->advancePolicyDueDate($entityManager, $entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof PremiumReceipt) return;

        $user = $this->getUser();

        // Agency Fallback
        if ($user && $user->getAgency() && !$entityInstance->getAgency()) {
            $entityInstance->setAgency($user->getAgency());
        }

        // Recalculate Commission (In case Amount changed)
        $this->calculateCommission($entityManager, $entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    // To calculate commission based on rules.
    private function calculateCommission(EntityManagerInterface $em, PremiumReceipt $receipt): void
    {
        $policy = $receipt->getPolicy();
        if (!$policy) return;

        $plan = $policy->getLicPlan();
        if (!$plan) return;

        // Calculate Policy Year
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
            $commission = ($receipt->getAmount() * $rule->getCommissionRate()) / 100;
            $receipt->setCommissionEarned($commission);
        } else {
            $receipt->setCommissionEarned(0);
        }
    }

    // To advance the policy due date.
    private function advancePolicyDueDate(EntityManagerInterface $em, PremiumReceipt $receipt): void
    {
        $policy = $receipt->getPolicy();
        if ($policy && $policy->getNextDueDate()) {
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
    }

    // PDF Generator
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
