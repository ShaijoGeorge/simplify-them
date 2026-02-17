<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use App\Entity\Permission;
use App\Service\PermissionCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

class PermissionCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'permissions';
    }

    public static function getEntityFqcn(): string
    {
        return Permission::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Permission')
            ->setEntityLabelInPlural('Permissions');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        if ($this->permissionChecker->hasPermission($this->getModuleKey(), 'view')) {
            $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        // LEFT COLUMN
        yield FormField::addColumn(6);
        
        yield FormField::addFieldset('Permission Mapping')
            ->setIcon('fa fa-link')
            ->setHelp('Link a role to a module with specific access rights');

        yield AssociationField::new('role', 'Role')
            ->setColumns(12)
            ->setRequired(true);

        // Allow multiple modules ONLY when creating
        if ($pageName === Crud::PAGE_NEW) {
            yield AssociationField::new('module', 'Modules')
                ->setFormTypeOption('multiple', true)
                ->setFormTypeOption('mapped', false)
                ->setRequired(true)
                ->setColumns(12)
                ->setHelp('Select one or more modules to apply these permissions to.');
        } else {
            // Normal Single Select for Edit
            yield AssociationField::new('module', 'Module')
                ->setColumns(12)
                ->setRequired(true);
        }

        // RIGHT COLUMN
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Access Rights')
            ->setIcon('fa fa-shield-alt')
            ->setHelp('Define what this role can do with this module');

        yield BooleanField::new('canView', 'View Access')
            ->setColumns(6)
            ->setHelp('Can see the module');

        yield BooleanField::new('canCreate', 'Create Access')
            ->setColumns(6)
            ->setHelp('Can add new records');

        yield BooleanField::new('canEdit', 'Edit Access')
            ->setColumns(6)
            ->setHelp('Can modify records');

        yield BooleanField::new('canDelete', 'Delete Access')
            ->setColumns(6)
            ->setHelp('Can remove records');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Permission) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        // If 'module' is NULL, it means we are in the "Multi-Select" Create Mode
        // (because we set 'mapped' => false in configureFields)
        if ($entityInstance->getModule() === null) {
            
            // Get the raw form data from the request
            $request = $this->getContext()->getRequest();
            $formData = $request->request->all('Permission');
            
            // Get array of Module IDs selected by user
            $moduleIds = $formData['module'] ?? [];

            if (!empty($moduleIds) && is_array($moduleIds)) {
                $role = $entityInstance->getRole();

                $savedCount = 0;
                $skippedModules = [];

                // Loop through every selected module and create a permission
                foreach ($moduleIds as $moduleId) {
                    $module = $entityManager->getRepository(Module::class)->find($moduleId);
                    
                    if (!$module) continue;

                    // Check if permission already exists to prevent duplicate errors
                    $exists = $entityManager->getRepository(Permission::class)->findOneBy([
                        'role' => $role,
                        'module' => $module
                    ]);

                    if ($exists) {
                        $skippedModules[] = $module->getName();
                        continue; // Skip existing
                    }

                    // Create new Permission Entry
                    $newPerm = new Permission();
                    $newPerm->setRole($role);
                    $newPerm->setModule($module);
                    
                    // Copy booleans from the form input
                    $newPerm->setCanView($entityInstance->isCanView());
                    $newPerm->setCanCreate($entityInstance->isCanCreate());
                    $newPerm->setCanEdit($entityInstance->isCanEdit());
                    $newPerm->setCanDelete($entityInstance->isCanDelete());

                    parent::persistEntity($entityManager, $newPerm);
                    $savedCount++;
                }

                if ($savedCount > 0) {
                    $this->addFlash('success', sprintf('%d permission(s) saved successfully.', $savedCount));
                }
            
                if (!empty($skippedModules)) {
                    $this->addFlash('warning', sprintf(
                        'Already exists for: %s - skipped.',
                        implode(', ', $skippedModules)
                    ));
                }
                
                // Stop here (don't save the original 'entityInstance' because it's empty/invalid)
                return;
            }
        }

        // Fallback for Edit page or single save
        parent::persistEntity($entityManager, $entityInstance);
    }
}
