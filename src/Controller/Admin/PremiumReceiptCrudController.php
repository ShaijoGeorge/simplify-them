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
        yield AssociationField::new('agency')->hideOnForm()->hideOnIndex();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof PremiumReceipt) {
            // Set Agency
            $user = $this->getUser();
            if ($user && $user->getAgency()) {
                $entityInstance->setAgency($user->getAgency());
            }

            // Generate Receipt Number (Simple Random for now)
            if (!$entityInstance->getReceiptNumber()) {
                $entityInstance->setReceiptNumber('REC-' . strtoupper(uniqid()));
            }

            $user = $this->getUser();
            if ($user && $user->getAgency()) {
                $entityInstance->setAgency($user->getAgency());
            }
            if (!$entityInstance->getReceiptNumber()) {
                $entityInstance->setReceiptNumber('REC-' . strtoupper(uniqid()));
            }

            $policy = $entityInstance->getPolicy();
            $plan = $policy->getLicPlan();

            if ($policy && $plan) {
                // Calculate Policy Year
                // Formula: Difference in years between DOC and Payment Date + 1
                $doc = $policy->getCommencementDate();
                $payDate = $entityInstance->getPaymentDate();

                // Simple year diff logic
                $diff = $doc->diff($payDate);
                $policyYear = $diff->y + 1;

                // Get Policy Term
                $term = $policy->getPolicyTerm();

                // Find Matching Rule
                $rule = $entityManager->getRepository(CommissionRule::class)->createQueryBuilder('c')
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

                // Calculate & Set
                if ($rule) {
                    $commission = ($entityInstance->getAmount() * $rule->getCommissionRate()) / 100;
                    $entityInstance->setCommissionEarned($commission);
                } else {
                    // Fallback if no rule found (e.g., 0%)
                    $entityInstance->setCommissionEarned(0);
                }
            }

            // Update Policy Next Due Date
            $policy = $entityInstance->getPolicy();
            if ($policy && $policy->getNextDueDate()) {
                $newDueDate = clone $policy->getNextDueDate();

                $mode = strtoupper($policy->getPremiumMode());
                if (in_array($mode, ['YLY', 'YEARLY'])) $newDueDate->modify('+1 year');
                elseif (in_array($mode, ['HLY', 'HALF-YEARLY'])) $newDueDate->modify('+6 months');
                elseif (in_array($mode, ['QLY', 'QUARTERLY'])) $newDueDate->modify('+3 months');
                elseif (in_array($mode, ['MLY', 'MONTHLY', 'NACH'])) $newDueDate->modify('+1 month');

                $policy->setNextDueDate($newDueDate);
                $entityManager->persist($policy);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    // PDF Generator
    public function generatePdf(AdminContext $context, EntityManagerInterface $entityManager)
    {
        $receiptId = $context->getRequest()->query->get('entityId');
        $receipt = $entityManager->getRepository(PremiumReceipt::class)->find($receiptId);

        if (!$receipt) {
            throw $this->createNotFoundException('Receipt not found');
        }
        
        // HTML Template for the Receipt
        $html = '
        <html>
        <head><style>
            body { font-family: sans-serif; padding: 20px; border: 2px solid #333; }
            .header { text-align: center; color: #0056b3; }
            .details { margin-top: 20px; }
            .row { padding: 5px 0; border-bottom: 1px solid #eee; }
            .label { font-weight: bold; display: inline-block; width: 150px; }
            .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #777; }
        </style></head>
        <body>
            <div class="header">
                <h2>Premium Receipt</h2>
                <p>'. $receipt->getAgency()->getBusinessName() .'</p>
            </div>
            <div class="details">
                <div class="row"><span class="label">Receipt No:</span> '. $receipt->getReceiptNumber() .'</div>
                <div class="row"><span class="label">Date:</span> '. $receipt->getPaymentDate()->format('d-M-Y') .'</div>
                <div class="row"><span class="label">Policy No:</span> '. $receipt->getPolicy()->getPolicyNumber() .'</div>
                <div class="row"><span class="label">Client Name:</span> '. $receipt->getPolicy()->getClient()->getFirstName() .'</div>
                <div class="row"><span class="label">Plan:</span> '. $receipt->getPolicy()->getLicPlan()->getPlanName() .'</div>
                <br>
                <div class="row" style="font-size: 18px; color: green;">
                    <span class="label">Amount Paid:</span> ₹'. number_format($receipt->getAmount(), 2) .'
                </div>

                '. ($receipt->getPolicy()->getGst() > 0 ? '
                <div class="row">
                    <span class="label">GST (Incl.):</span> ₹'. number_format($receipt->getPolicy()->getGst(), 2) .'
                </div>' : '') .'

                <div class="row"><span class="label">Mode:</span> '. $receipt->getPaymentMode() .'</div>
            </div>
            <div class="footer">
                <p>This is a computer-generated receipt.</p>
                <p>Contact: '. $receipt->getAgency()->getMobile() .'</p>
            </div>
        </body>
        </html>';

        // Convert HTML to PDF
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
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
                'Content-Disposition' => 'inline; filename="receipt.pdf"',
            ]
        );
    }
}
