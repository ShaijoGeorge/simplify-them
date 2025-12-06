<?php

namespace App\Controller\Admin;

use App\Entity\Agency;
use App\Entity\LicPlan;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
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

        // SETTINGS
        yield MenuItem::section('SETTINGS');
        yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
    }
}
