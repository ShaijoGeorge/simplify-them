<?php

namespace App\Controller\Admin;

use App\Entity\PolicyDocument;
use App\Entity\User;
use App\Repository\PolicyDocumentRepository;
use App\Repository\PolicyRepository;
use App\Service\PermissionCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PolicyDocumentCrudController extends BaseCrudController
{
    public function __construct(
        PermissionCheckerService $permissionChecker,
        private RequestStack $requestStack,
        private PolicyRepository $policyRepository,
        private PolicyDocumentRepository $documentRepository,
        private string $projectDir
    ) {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'policy_documents';
    }

    public static function getEntityFqcn(): string
    {
        return PolicyDocument::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Document')
            ->setEntityLabelInPlural('Policy Documents')
            ->setDefaultSort(['uploadedAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        if ($this->permissionChecker->hasPermission($this->getModuleKey(), 'view')) {
            $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        }

        // Download / View action
        $downloadAction = Action::new('downloadDocument', 'Download', 'fa fa-download')
            ->linkToUrl(function (PolicyDocument $doc): string {
                return $this->container->get('router')->generate('admin_policy_document_download', [
                    'id' => $doc->getId(),
                ]);
            })
            ->setCssClass('btn btn-sm btn-outline-success');

        $actions->add(Crud::PAGE_DETAIL, $downloadAction);

        return $actions;
    }

    public function createEntity(string $entityFqcn)
    {
        $document = new PolicyDocument();

        // Pre-fill policy if policyId is passed (from Policy/Claim pages)
        $request = $this->requestStack->getCurrentRequest();
        $policyId = $request?->query->get('policyId');
        if ($policyId) {
            $policy = $this->policyRepository->find($policyId);
            if ($policy) {
                $document->setPolicy($policy);
            }
        }

        return $document;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var User $user */
        $user = $this->getUser();

        // LEFT COLUMN: DOCUMENT INFO
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Document Information')
            ->setIcon('fa fa-file-pdf')
            ->setHelp('Upload policy-related documents (PDF, JPG, PNG — max 10 MB)');

        $policyField = AssociationField::new('policy', 'Policy')
            ->setRequired(true)
            ->setColumns(12);

        // If policyId is passed, disable the dropdown so the user can't change it
        $request = $this->requestStack->getCurrentRequest();
        $policyId = $request?->query->get('policyId');
        if ($policyId) {
            $policyField->setFormTypeOption('disabled', true);
        }
        yield $policyField;

        yield ChoiceField::new('documentType', 'Document Type')
            ->setChoices(array_flip(PolicyDocument::DOCUMENT_TYPES))
            ->renderAsBadges([
                PolicyDocument::TYPE_POLICY_BOND       => 'primary',
                PolicyDocument::TYPE_REVIVAL_LETTER    => 'info',
                PolicyDocument::TYPE_ASSIGNMENT_DEED   => 'warning',
                PolicyDocument::TYPE_DISCHARGE_VOUCHER => 'success',
                PolicyDocument::TYPE_CLAIM_FORM        => 'danger',
                PolicyDocument::TYPE_OTHER             => 'secondary',
            ])
            ->setRequired(true)
            ->setColumns(12);

        // RIGHT COLUMN: FILE UPLOAD & AUDIT
        yield FormField::addColumn(6);

        yield FormField::addFieldset('File Upload')
            ->setIcon('fa fa-upload');

        // VichUploader file field - only on form pages
        if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
            yield TextField::new('documentFile', 'File')
                ->setFormType(VichFileType::class)
                ->setFormTypeOptions([
                    'allow_delete' => false,
                    'download_uri' => false,
                ])
                ->setRequired($pageName === Crud::PAGE_NEW)
                ->setColumns(12)
                ->setHelp('Accepted: PDF, JPG, PNG (max 10 MB)');
        }

        yield TextField::new('fileName', 'Display Name')
            ->setColumns(12)
            ->setHelp('Original file name for quick reference')
            ->setRequired(true);

        yield TextField::new('filePath', 'File Path')
            ->onlyOnDetail();

        // AUDIT
        yield FormField::addFieldset('Audit Info')
            ->setIcon('fa fa-database')
            ->hideOnForm();

        yield TextField::new('uploadedBy', 'Uploaded By')
            ->hideOnForm();

        yield DateTimeField::new('uploadedAt', 'Uploaded At')
            ->hideOnForm();

        if ($pageName === Crud::PAGE_INDEX) {
            yield IdField::new('id', 'View')
                ->setTemplatePath('Admin/field/document_view_link.html.twig')
                ->setSortable(false);

            yield IdField::new('id', 'Download')
                ->setTemplatePath('Admin/field/document_download_link.html.twig')
                ->setSortable(false);
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof PolicyDocument) {
            // If fileName was not manually set, derive from the uploaded file
            if (!$entityInstance->getFileName() && $entityInstance->getDocumentFile()) {
                $entityInstance->setFileName($entityInstance->getDocumentFile()->getClientOriginalName());
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof PolicyDocument) {
            // Update fileName if a new file was uploaded
            if ($entityInstance->getDocumentFile()) {
                $entityInstance->setFileName($entityInstance->getDocumentFile()->getClientOriginalName());
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('documentType')
            ->add('policy');
    }

    // ── Download Route ─────────────────────────────────────────────

    #[Route('/admin/policy-document/{id}/download', name: 'admin_policy_document_download', methods: ['GET'])]
    public function downloadDocument(int $id, \Symfony\Component\HttpFoundation\Request $request): BinaryFileResponse
    {
        $document = $this->documentRepository->find($id);

        if (!$document || !$document->getFilePath()) {
            throw new NotFoundHttpException('Document not found.');
        }

        $absolutePath = $this->projectDir . '/var/storage/policy_docs/' . $document->getFilePath();

        if (!file_exists($absolutePath)) {
            throw new NotFoundHttpException('File not found on disk.');
        }

        $response = new BinaryFileResponse($absolutePath);

        // Use the human-friendly fileName for the download, fallback to filePath
        $downloadName = $document->getFileName() ?? $document->getFilePath();
        
        // If 'download' param is present, force attachment
        $disposition = $request->query->get('download') 
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT 
            : ResponseHeaderBag::DISPOSITION_INLINE;

        $response->setContentDisposition(
            $disposition,
            $downloadName
        );

        return $response;
    }
}
