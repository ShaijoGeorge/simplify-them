<?php

namespace App\Controller\Admin;

use App\Entity\Agency;
use App\Entity\Client;
use App\Entity\CommissionRule;
use App\Entity\LicPlan;
use App\Entity\LicPlanType;
use App\Entity\Module;
use App\Entity\Permission;
use App\Entity\Policy;
use App\Entity\PremiumReceipt;
use App\Entity\Role;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\PolicyRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private PolicyRepository $policyRepository,
        private ClientRepository $clientRepository
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

        return $this->render('Admin/dashboard.html.twig', [
            'due_amount' => $dueAmount,
            'birthdays' => $birthdays,
            'lapsed_count' => $lapsedCount,
            'current_month' => date('F'),
        ]);
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
        yield MenuItem::linkToCrud('Plan Types', 'fa fa-tags', LicPlanType::class);
    
        // AGENT TOOLS
        yield MenuItem::section('MY OFFICE');
        yield MenuItem::linkToCrud('Clients', 'fa fa-users', Client::class);
        yield MenuItem::linkToCrud('Policies', 'fa fa-file-contract', Policy::class);
        yield MenuItem::linkToCrud('Premium Collection', 'fa fa-rupee-sign', PremiumReceipt::class);

        // SETTINGS
        yield MenuItem::section('SETTINGS');
        yield MenuItem::linkToCrud('Modules', 'fa fa-cubes', Module::class);
        yield MenuItem::linkToCrud('Permissions', 'fa fa-lock', Permission::class);
        yield MenuItem::linkToCrud('Roles', 'fa fa-user-tag', Role::class);
        yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
    }
}
