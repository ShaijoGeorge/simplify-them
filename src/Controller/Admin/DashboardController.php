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
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\PolicyRepository;
use App\Repository\SurvivalBenefitRepository;
use App\Repository\PremiumReceiptRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    public function configureAssets(): \EasyCorp\Bundle\EasyAdminBundle\Config\Assets
    {
        return parent::configureAssets()
            ->addCssFile('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&display=swap')
            ->addCssFile('https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap')
            ->addCssFile('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&display=swap')
            ->addCssFile('assets/css/admin/theme.css')
            ->addCssFile('assets/css/admin/dashboard.css')
            ->addCssFile('assets/css/admin/detail.css')
            ->addCssFile('assets/css/admin/ledger.css');
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
        yield MenuItem::linkToRoute('Commission Statement', 'fa fa-file-invoice-dollar', 'admin_commission_statement');

        // SETTINGS
        yield MenuItem::section('SETTINGS');
        yield MenuItem::linkToCrud('Modules', 'fa fa-cubes', Module::class);
        yield MenuItem::linkToCrud('Permissions', 'fa fa-lock', Permission::class);
        yield MenuItem::linkToCrud('Roles', 'fa fa-user-tag', Role::class);
        yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
    }
}
