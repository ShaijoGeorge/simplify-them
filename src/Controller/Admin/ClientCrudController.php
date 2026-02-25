<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\User;
use App\Service\ClientImportService;
use App\Service\PermissionCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class ClientCrudController extends BaseCrudController
{
    public function __construct(
        private ClientImportService $importService,
        private EntityManagerInterface $entityManager,
        PermissionCheckerService $permissionChecker
    ) {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'clients';
    }

    public static function getEntityFqcn(): string
    {
        return Client::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Client')
            ->setEntityLabelInPlural('Clients')
            ->overrideTemplate('crud/index', 'Admin/client/index.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        $importAction = Action::new('importClients', 'Import Excel')
            ->createAsGlobalAction()
            ->setCssClass('btn btn-success')
            ->setHtmlAttributes(['data-bs-toggle' => 'modal', 'data-bs-target' => '#importModal'])
            ->linkToUrl('#'); // just JS trigger

        // Family Portfolio
        $portfolioAction = Action::new('familyPortfolio', 'Family Report', 'fa fa-users')
            ->linkToCrudAction('generateFamilyPortfolio');

        if ($this->permissionChecker->hasPermission($this->getModuleKey(), 'create')) {
            $actions->add(Crud::PAGE_INDEX, $importAction);
        }
        
        if ($this->permissionChecker->hasPermission($this->getModuleKey(), 'view')) {
            $actions
                ->add(Crud::PAGE_INDEX, $portfolioAction)
                ->add(Crud::PAGE_DETAIL, $portfolioAction)
                ->add(Crud::PAGE_INDEX, Action::DETAIL);
        }

        return $actions;
    }

    public function createEntity(string $entityFqcn)
    {
        $client = new Client();
        $currentUser = $this->getUser();

        if ($currentUser && $currentUser->getAgency()) {
            $client->setAgency($currentUser->getAgency());
        }

        return $client;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var User $user */
        $user = $this->getUser();
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles());

        // LEFT COLUMN: IDENTITY
        yield FormField::addColumn(6);
        
        yield FormField::addFieldset('Personal Information')
            ->setIcon('fa fa-user');

        yield TextField::new('name', 'Client Name')
            ->setColumns(12);
            
        yield DateField::new('dob', 'Date of Birth')->setColumns(12);

        yield FormField::addFieldset('Family Grouping')
            ->setIcon('fa fa-users')
            ->setHelp('Link this client to a family head for group management');

        yield AssociationField::new('headOfFamily', 'Head of Family')
            ->setColumns(12);

        // RIGHT COLUMN: CONTACT
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Contact Details')
            ->setIcon('fa fa-address-book');

        yield TelephoneField::new('mobile', 'Mobile No')
            ->setColumns(6)
            ->setFormTypeOption('attr', [
                'data-intl-phone' => 'true',
                'data-default-country' => 'in',
            ]);
        yield EmailField::new('email', 'Email')->setColumns(6);

        yield FormField::addFieldset('Address & Location')
            ->setIcon('fa fa-map-marker-alt');

        yield TextareaField::new('address')->hideOnIndex()->setColumns(12);
        yield TextField::new('city')->setColumns(6);
        yield TextField::new('pincode')->setColumns(6);

        // SYSTEM FIELDS
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
        // Check if we are saving a Client
        if ($entityInstance instanceof Client) {
            // Get the current User and their Agency
            $user = $this->getUser();
            if ($user && $user->getAgency()) {
                // Set the Agency automatically
                $entityInstance->setAgency($user->getAgency());
            }
        }

        // Continue with the standard save process
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Client) {
            $user = $this->getUser();
            if ($user && $user->getAgency() && !$entityInstance->getAgency()) {
                $entityInstance->setAgency($user->getAgency());
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    // File Upload
    #[Route('/admin/client/import', name: 'app_client_import', methods: ['POST'])]
    public function processImport(Request $request): Response
    {
        $file = $request->files->get('file');
        
        if ($file) {
            $user = $this->getUser();
            if ($user && $user->getAgency()) {
                $count = $this->importService->importClients($file, $user->getAgency());
                $this->addFlash('success', "$count Clients imported successfully!");
            }
        } else {
            $this->addFlash('danger', "No file uploaded.");
        }

        return $this->redirect($request->get('returnUrl'));
    }

    // Download example
    #[Route('/admin/export/client-template', name: 'app_client_download_example', methods: ['GET'])]
    public function downloadExample(): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Full Headers
        $headers = [
            'Name',         // Col A
            'DOB (Y-m-d)',  // Col B
            'Mobile',       // Col C
            'Email',        // Col D
            'Address',      // Col E
            'City',         // Col F
            'Pincode'       // Col G
        ];
        $sheet->fromArray($headers, null, 'A1');

        // Sample Data
        $sample = [
            'P M Moidunni', 
            '1990-01-01', 
            '9876543210',
            'moidunni@example.com',
            '123 MG Road',
            'Kottayam',
            '686001'
        ];
        $sheet->fromArray($sample, null, 'A2');

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create Response
        $writer = new Xlsx($spreadsheet);
        
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Client_Import_Template.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    public function generateFamilyPortfolio(AdminContext $context, EntityManagerInterface $entityManager)
    {
        $clientId = $context->getRequest()->query->get('entityId');
        $client = $entityManager->getRepository(Client::class)->find($clientId);

        if (!$client) {
            throw $this->createNotFoundException('Client not found');
        }

        $agency = $client->getAgency();

        // LOGIC: Find the full family tree
        // If this client has a Head of Family, fetch the Head first
        $head = $client->getHeadOfFamily() ? $client->getHeadOfFamily() : $client;
        
        // Fetch all members (Head + Children/Spouse)
        $familyMembers = [$head];
        foreach ($head->getFamilyMembers() as $member) {
            $familyMembers[] = $member;
        }

        // Calculate Totals
        $totalSumAssured = 0;
        $totalPremium = 0;
        $totalPolicies = 0;

        foreach ($familyMembers as $member) {
            foreach ($member->getPolicies() as $policy) {
                if ($policy->getStatus() == 'IN_FORCE') {
                    // Only count active policies for the report
                    $totalSumAssured += $policy->getSumAssured();
                    $totalPremium += $policy->getTotalPremium();
                    $totalPolicies++;
                }
            }
        }

        // HTML Template for PDF
        $html = $this->renderView('Admin/client/family_portfolio.html.twig', [
            'head' => $head,
            'members' => $familyMembers,
            'agency' => $agency,
            'stats' => [
                'total_sa' => $totalSumAssured,
                'total_prem' => $totalPremium,
                'count' => $totalPolicies
            ]
        ]);

        // Generate PDF
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true); // Allow images
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Family_Portfolio_'.$head->getName().'.pdf"',
            ]
        );
    }

}
