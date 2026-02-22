<?php

namespace App\Controller\Admin;

use App\Service\PermissionCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class BaseCrudController extends AbstractCrudController
{
    protected PermissionCheckerService $permissionChecker;

    public function __construct(PermissionCheckerService $permissionChecker)
    {
        $this->permissionChecker = $permissionChecker;
    }

    // Every child controller MUST define module key
    abstract protected function getModuleKey(): string;

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile('https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/css/intlTelInput.css')
            ->addCssFile('assets/css/admin/intl_mobile_input.css')
            ->addJsFile('https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/js/intlTelInput.min.js')
            ->addJsFile('assets/js/admin/intl_mobile_input.js');
    }

    public function configureActions(Actions $actions): Actions
    {
        $module = $this->getModuleKey();

        if (!$this->permissionChecker->hasPermission($module, 'view')) {
            $actions->disable(Action::DETAIL);
        }

        if (!$this->permissionChecker->hasPermission($module, 'create')) {
            $actions->disable(Action::NEW);
        }

        if (!$this->permissionChecker->hasPermission($module, 'edit')) {
            $actions->disable(Action::EDIT);
        }

        if (!$this->permissionChecker->hasPermission($module, 'delete')) {
            $actions->disable(Action::DELETE);
        }

        return $actions;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$this->permissionChecker->hasPermission($this->getModuleKey(), 'create')) {
            throw new AccessDeniedHttpException('You do not have permission to create this record.');
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$this->permissionChecker->hasPermission($this->getModuleKey(), 'edit')) {
            throw new AccessDeniedHttpException('You do not have permission to edit this record.');
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$this->permissionChecker->hasPermission($this->getModuleKey(), 'delete')) {
            throw new AccessDeniedHttpException('You do not have permission to delete this record.');
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
