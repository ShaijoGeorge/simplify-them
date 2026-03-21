<?php

namespace App\Controller\Admin;

use App\Entity\Agency;
use App\Entity\Client;
use App\Entity\BonusRate;
use App\Entity\ClientTransaction;
use App\Entity\CommissionRule;
use App\Repository\ClientTransactionRepository;
use App\Entity\LicPlan;
use App\Entity\LicPlanType;
use App\Entity\Module;
use App\Entity\Nominee;
use App\Entity\Permission;
use App\Entity\Policy;
use App\Entity\PolicyRider;
use App\Entity\PremiumReceipt;
use App\Entity\PremiumTable;
use App\Entity\Role;
use App\Entity\SaRebate;
use App\Entity\SurvivalBenefit;
use App\Entity\Claim;
use App\Entity\PolicyDocument;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\PolicyRepository;
use App\Repository\SurvivalBenefitRepository;
use App\Repository\PremiumReceiptRepository;
use App\Service\DueListService;
use App\Service\WhatsAppService;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private PolicyRepository $policyRepository,
        private ClientRepository $clientRepository,
        private SurvivalBenefitRepository $survivalBenefitRepository,
        private PremiumReceiptRepository $receiptRepository,
        private ClientTransactionRepository $txnRepository,
        private DueListService $dueListService,
        private WhatsAppService $whatsAppService,
    ) {}

    public function index(): Response
    {
        // Get the current User and Agency
        /** @var User $user */
        $user = $this->getUser();
        $agencyId = $user->getAgency() ? $user->getAgency()->getId() : 0;

        // Fetch Data
        $dueAmount = $this->policyRepository->getPremiumDueAmountThisMonth($agencyId);
        $birthdays = $this->clientRepository->findBirthdaysThisMonth($agencyId);
        $lapsedCount = $this->policyRepository->countRevivalOpportunities($agencyId);
        $survivalBenefits = $this->survivalBenefitRepository->findPendingDueThisMonth($agencyId);

        // New metrics
        $activePolicies = $this->policyRepository->countActivePolicies($agencyId);
        $totalClients = $this->clientRepository->countByAgency($agencyId);
        $collectedThisMonth = $this->receiptRepository->getCollectedThisMonth($agencyId);

        // Time-of-day greeting
        $hour = (int) date('G');
        if ($hour < 12) {
            $greeting = 'Good morning';
        } elseif ($hour < 17) {
            $greeting = 'Good afternoon';
        } else {
            $greeting = 'Good evening';
        }

        return $this->render('Admin/dashboard.html.twig', [
            'due_amount' => $dueAmount,
            'birthdays' => $birthdays,
            'lapsed_count' => $lapsedCount,
            'survival_benefits' => $survivalBenefits,
            'current_month' => date('F'),
            'active_policies' => $activePolicies,
            'total_clients' => $totalClients,
            'collected_this_month' => $collectedThisMonth,
            'greeting' => $greeting,
            'user_name' => $user->getFullName() ?? $user->getUserIdentifier(),
            'today_date' => date('l, d F Y'),
        ]);
    }

    #[Route('/admin/commission-statement', name: 'admin_commission_statement')]
    public function commissionStatement(Request $request): Response
    {
        $user = $this->getUser();
        $agency = $user->getAgency();

        if (!$agency) {
            throw $this->createAccessDeniedException('No agency assigned.');
        }

        // Default to current month
        $monthParam = $request->query->get('month', date('Y-m'));
        [$year, $month] = explode('-', $monthParam);

        $data = $this->receiptRepository->getMonthlyCommissionStatement(
            $agency->getId(),
            (int) $year,
            (int) $month,
        );

        // Build prev/next month links
        $current = new \DateTime("$year-$month-01");
        $prev = (clone $current)->modify('-1 month')->format('Y-m');
        $next = (clone $current)->modify('+1 month')->format('Y-m');

        return $this->render('Admin/commission_statement/index.html.twig', [
            'agency'   => $agency,
            'totals'   => $data['totals'],
            'receipts' => $data['receipts'],
            'month'    => $current,
            'prevMonth' => $prev,
            'nextMonth' => $next,
            'hasPan'   => $agency->hasPan(),
        ]);
    }

    #[Route('/admin/client-balances', name: 'admin_client_balances')]
    public function clientBalances(): Response
    {
        $user = $this->getUser();
        $agency = $user->getAgency();

        if (!$agency) {
            throw $this->createAccessDeniedException('No agency assigned.');
        }

        $balances = $this->txnRepository->getOutstandingBalances($agency);

        // Compute totals
        $totalReceivable = 0.0; // clients owe agency (positive balances)
        $totalPayable    = 0.0; // agency owes clients (negative balances)

        foreach ($balances as $entry) {
            if ($entry['balance'] > 0) {
                $totalReceivable += $entry['balance'];
            } else {
                $totalPayable += abs($entry['balance']);
            }
        }

        return $this->render('Admin/client_balances/index.html.twig', [
            'balances'         => $balances,
            'total_receivable' => round($totalReceivable, 2),
            'total_payable'    => round($totalPayable, 2),
            'net_position'     => round($totalReceivable - $totalPayable, 2),
            'agency'           => $agency,
        ]);
    }

    #[Route('/admin/client-ledger/{id}', name: 'admin_client_ledger')]
    public function clientLedger(Client $client): Response
    {
        $user = $this->getUser();
        $agency = $user->getAgency();

        if (!$agency) {
            throw $this->createAccessDeniedException('No agency assigned.');
        }

        // Verify client belongs to this agency
        if ($client->getAgency()?->getId() !== $agency->getId()) {
            throw $this->createAccessDeniedException('Client does not belong to your agency.');
        }

        $ledger  = $this->txnRepository->getClientLedger($client, $agency);
        $balance = $this->txnRepository->getClientBalance($client, $agency);

        // Compute totals
        $totalCollected = 0.0;
        $totalPaidToLic = 0.0;

        foreach ($ledger as $entry) {
            $txn = $entry['transaction'];
            $amt = (float) $txn->getAmount();
            if ($txn->getType() === ClientTransaction::TYPE_COLLECTION) {
                $totalCollected += $amt;
            } elseif ($txn->getType() === ClientTransaction::TYPE_PAID_TO_LIC) {
                $totalPaidToLic += $amt;
            }
        }

        return $this->render('Admin/client_balances/ledger.html.twig', [
            'client'           => $client,
            'ledger'           => $ledger,
            'balance'          => $balance,
            'total_collected'  => round($totalCollected, 2),
            'total_paid_to_lic'=> round($totalPaidToLic, 2),
        ]);
    }

    /**
     * Parse filter params common to both report and export actions.
     *
     * The date range is always fixed: 6 months back to 3 months forward from today.
     * All three zones (Recovery, Current, Pipeline) are loaded simultaneously.
     * The fup_month filter narrows within this range (optional).
     *
     * @return array{fromDate: \DateTime, toDate: \DateTime, filters: array<string, mixed>}
     */
    private function parseDueListParams(Request $request): array
    {
        $today = new \DateTime('today');
        $fromDate = (clone $today)->modify('-6 months')->modify('first day of this month');
        $toDate = (clone $today)->modify('+3 months')->modify('last day of this month 23:59:59');

        $filters = array_filter([
            'fup_month'    => $request->query->get('fup_month', ''),
            'mode'         => $request->query->get('mode', ''),
            'flag'         => $request->query->get('flag', ''),
            'grace_status' => $request->query->get('grace_status', ''),
            'sort'         => $request->query->get('sort', ''),
        ]);

        return [
            'fromDate' => $fromDate,
            'toDate'   => $toDate,
            'filters'  => $filters,
        ];
    }

    #[Route('/admin/renewal-due-report', name: 'admin_renewal_due_report')]
    public function renewalDueReport(Request $request): Response
    {
        $user = $this->getUser();
        $agency = $user->getAgency();

        if (!$agency) {
            throw $this->createAccessDeniedException('No agency assigned.');
        }

        $params = $this->parseDueListParams($request);
        $entries = $this->dueListService->buildDueList(
            $agency,
            $params['fromDate'],
            $params['toDate'],
            $params['filters'],
        );
        $summary = $this->dueListService->computeSummary($entries);

        // Group entries by zone and generate WhatsApp URLs
        $zones = ['past' => [], 'current' => [], 'future' => []];

        foreach ($entries as &$entry) {
            $policy = $entry['policy'];
            $client = $policy->getClient();
            $mobile = $client?->getMobile();

            $entry['whatsapp_url'] = null;
            if ($mobile) {
                $entry['whatsapp_url'] = $this->whatsAppService->generateWhatsAppUrl(
                    $mobile,
                    $client->getName() ?? '',
                    $policy->getPolicyNumber() ?? '',
                    $policy->getFup()?->format('d-M-Y') ?? '',
                    number_format($entry['total_payable'], 2),
                );
            }

            $zones[$entry['zone']][] = $entry;
        }
        unset($entry);

        // Build FUP month options for filter dropdown (past 6 to future 3)
        $fupMonthOptions = [];
        $today = new \DateTime('today');
        for ($i = -6; $i <= 3; $i++) {
            $m = (clone $today)->modify("{$i} months");
            $fupMonthOptions[$m->format('Y-m')] = $m->format('M Y');
        }

        return $this->render('Admin/renewal_due/index.html.twig', [
            'zones'             => $zones,
            'summary'           => $summary,
            'agency'            => $agency,
            'filters'           => $params['filters'],
            'fup_month_options' => $fupMonthOptions,
        ]);
    }

    #[Route('/admin/renewal-due-report/export', name: 'admin_renewal_due_export')]
    public function renewalDueExport(Request $request): Response
    {
        $user = $this->getUser();
        $agency = $user->getAgency();

        if (!$agency) {
            throw $this->createAccessDeniedException('No agency assigned.');
        }

        $params = $this->parseDueListParams($request);
        $entries = $this->dueListService->buildDueList(
            $agency,
            $params['fromDate'],
            $params['toDate'],
            $params['filters'],
        );

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Due List');

        $flagLabels = ['FY' => 'First Year', 'ST' => 'Standard', 'LP' => 'Last Premium', 'MT' => 'Matures After Due'];
        $graceLabels = ['upcoming' => 'Upcoming', 'due_today' => 'Due Today', 'in_grace' => 'In Grace', 'overdue' => 'Overdue'];
        $zoneLabels = ['past' => 'Recovery', 'current' => 'Current', 'future' => 'Pipeline'];

        // Headers
        $headers = [
            '#', 'Zone', 'Name of Assured', 'Mobile', 'Policy Number', 'Plan/Term',
            'D.o.C', 'FUP', 'Inst. Premium', 'Pending Due',
            'Late Fee', 'GST', 'GST Rate', 'Total Payable',
            'Mode', 'Flag', 'Est. Commission', 'Status',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:R1')->getFont()->setBold(true);

        // Data rows
        $row = 2;
        foreach ($entries as $i => $entry) {
            $policy = $entry['policy'];
            $plan = $policy->getLicPlan();
            $planTerm = $plan
                ? $plan->getTableNumber() . '/' . $policy->getPolicyTerm()
                : '';

            $sheet->fromArray([
                $i + 1,
                $zoneLabels[$entry['zone']] ?? $entry['zone'],
                $policy->getClient()?->getName(),
                $policy->getClient()?->getMobile(),
                $policy->getPolicyNumber(),
                $planTerm,
                $policy->getCommencementDate()?->format('d-M-Y') ?? '',
                $policy->getFup()?->format('m/Y') ?? '',
                $entry['basic_premium'],
                $entry['pending_installments'],
                $entry['late_fee'] + $entry['late_fee_gst'],
                $entry['total_gst'],
                $entry['gst_reason'],
                $entry['total_payable'],
                $policy->getPremiumMode(),
                $flagLabels[$entry['flag']] ?? $entry['flag'],
                $entry['est_commission'],
                $graceLabels[$entry['grace_status']] ?? $entry['grace_status'],
            ], null, "A{$row}");
            $row++;
        }

        // Summary row
        if (count($entries) > 0) {
            $summary = $this->dueListService->computeSummary($entries);
            $sheet->fromArray([
                '', '', '', '', '', '', '', 'TOTAL', '', '',
                $summary['total_late_fee'],
                '', '',
                $summary['total_payable'],
                '', '',
                $summary['total_commission'],
                '',
            ], null, "A{$row}");
            $sheet->getStyle("A{$row}:R{$row}")->getFont()->setBold(true);
        }

        // Auto-size columns
        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Number format for currency columns (I, K, L, N, Q)
        $lastDataRow = max($row, 2);
        foreach (['I', 'K', 'L', 'N', 'Q'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastDataRow}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $fupFilter = $params['filters']['fup_month'] ?? '';
        $filename = $fupFilter
            ? 'Due_List_' . str_replace('-', '_', $fupFilter) . '.xlsx'
            : 'Due_List_' . date('Ymd') . '.xlsx';
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', "attachment;filename=\"{$filename}\"");
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    public function configureAssets(): \EasyCorp\Bundle\EasyAdminBundle\Config\Assets
    {
        return parent::configureAssets()
            ->addCssFile('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&display=swap')
            ->addCssFile('https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap')
            ->addCssFile('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&display=swap')
            ->addCssFile('assets/css/admin/theme.css')
            ->addCssFile('assets/css/admin/dashboard.css')
            ->addCssFile('assets/css/admin/detail.css')
            ->addCssFile('assets/css/admin/ledger.css')
            ->addCssFile('assets/css/admin/commands.css');
    }


    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SimplifyThem');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        
        // SUPER ADMIN
        yield MenuItem::section('PLATFORM ADMIN');
        yield MenuItem::linkToCrud('Agencies (Tenants)', 'fa fa-building', Agency::class);
        yield MenuItem::linkToCrud('LIC Plans', 'fa fa-book', LicPlan::class);
        yield MenuItem::linkToCrud('Commission Rules', 'fa fa-percentage', CommissionRule::class);
        yield MenuItem::linkToCrud('Bonus Rates', 'fa fa-chart-line', BonusRate::class);
        yield MenuItem::linkToCrud('Premium Tables', 'fa fa-table', PremiumTable::class);
        yield MenuItem::linkToCrud('SA Rebates', 'fa fa-sliders', SaRebate::class);
        yield MenuItem::linkToCrud('Plan Types', 'fa fa-tags', LicPlanType::class);
    
        // AGENT TOOLS
        yield MenuItem::section('MY OFFICE');
        yield MenuItem::linkToCrud('Clients', 'fa fa-users', Client::class);
        yield MenuItem::linkToCrud('Policies', 'fa fa-file-contract', Policy::class);
        yield MenuItem::linkToRoute('Quick Policy Entry', 'fa fa-bolt', 'app_quick_policy');
        yield MenuItem::linkToCrud('Premium Collection', 'fa fa-rupee-sign', PremiumReceipt::class);
        yield MenuItem::linkToCrud('Client Transactions', 'fa fa-exchange-alt', ClientTransaction::class);
        yield MenuItem::linkToRoute('Client Balances', 'fa fa-scale-balanced', 'admin_client_balances');
        yield MenuItem::linkToCrud('Nominees', 'fa fa-user-shield', Nominee::class);
        yield MenuItem::linkToCrud('Policy Riders', 'fa fa-shield-halved', PolicyRider::class);
        yield MenuItem::linkToCrud('Survival Benefits', 'fa fa-hand-holding-dollar', SurvivalBenefit::class);
        yield MenuItem::linkToCrud('Claims', 'fa fa-file-medical', Claim::class);
        yield MenuItem::linkToCrud('Documents', 'fa fa-file-pdf', PolicyDocument::class);
        yield MenuItem::linkToRoute('Commission Statement', 'fa fa-file-invoice-dollar', 'admin_commission_statement');
        yield MenuItem::linkToRoute('Renewal Due Report', 'fa fa-calendar-check', 'admin_renewal_due_report');

        // TOOLS
        yield MenuItem::section('TOOLS');
        yield MenuItem::linkToRoute('Commands', 'fa fa-terminal', 'admin_commands');

        // SETTINGS
        yield MenuItem::section('SETTINGS');
        yield MenuItem::linkToCrud('Modules', 'fa fa-cubes', Module::class);
        yield MenuItem::linkToCrud('Permissions', 'fa fa-lock', Permission::class);
        yield MenuItem::linkToCrud('Roles', 'fa fa-user-tag', Role::class);
        yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
    }
}
