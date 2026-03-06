<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\PermissionCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
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

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user || $user->isAdministrator()) {
            return $qb;
        }

        $agency = $user->getAgency();
        if (!$agency) {
            return $qb->andWhere('1 = 0');
        }

        $rootAlias = $qb->getRootAliases()[0] ?? 'entity';
        $em = $this->container->get('doctrine')->getManager();
        $metadata = $em->getClassMetadata($entityDto->getFqcn());

        if ($metadata->hasAssociation('agency')) {
            return $qb
                ->andWhere(sprintf('%s.agency = :agency', $rootAlias))
                ->setParameter('agency', $agency);
        }

        if ($metadata->hasAssociation('policy')) {
            $qb->leftJoin(sprintf('%s.policy', $rootAlias), 'agency_scope_policy');
            return $qb
                ->andWhere('agency_scope_policy.agency = :agency')
                ->setParameter('agency', $agency);
        }

        if ($metadata->hasAssociation('client')) {
            $qb->leftJoin(sprintf('%s.client', $rootAlias), 'agency_scope_client');
            return $qb
                ->andWhere('agency_scope_client.agency = :agency')
                ->setParameter('agency', $agency);
        }

        if ($metadata->hasAssociation('role')) {
            $qb->leftJoin(sprintf('%s.role', $rootAlias), 'agency_scope_role');
            return $qb
                ->andWhere('agency_scope_role.agency = :agency')
                ->setParameter('agency', $agency);
        }

        return $qb;
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
