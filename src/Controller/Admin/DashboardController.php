<?php

namespace App\Controller\Admin;

use App\Entity\Agency;
use App\Entity\Client;
use App\Entity\LicPlan;
use App\Entity\Policy;
use App\Entity\PremiumReceipt;
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
        $user = $this->getUser();
        $agencyId = $user->getAgency() ? $user->getAgency()->getId() : 0;

        // Fetch Data
        $dueAmount = $this->policyRepository->getPremiumDueAmountThisMonth($agencyId);
        $birthdays = $this->clientRepository->findBirthdaysThisMonth($agencyId);

        return $this->render('admin/dashboard.html.twig', [
            'due_amount' => $dueAmount,
            'birthdays' => $birthdays,
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
    
        // AGENT TOOLS
        yield MenuItem::section('MY OFFICE');
        yield MenuItem::linkToCrud('Clients', 'fa fa-users', Client::class);
        yield MenuItem::linkToCrud('Policies', 'fa fa-file-contract', Policy::class);
        yield MenuItem::linkToCrud('Premium Collection', 'fa fa-rupee-sign', PremiumReceipt::class);

        // SETTINGS
        yield MenuItem::section('SETTINGS');
        yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
    }
}
