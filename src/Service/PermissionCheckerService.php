<?php

namespace App\Service;

use App\Entity\Permission;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class PermissionCheckerService
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {}

    // Check if the logged-in user has permission for a specific module action
    public function hasPermission(string $moduleKey, string $action): bool
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Super Admins can do EVERYTHING
        if ($user->isAdministrator()) {
            return true;
        }

        // If user has no assigned role, they can't do anything
        $role = $user->getUserRole();
        if (!$role) {
            return false;
        }

        // Fetch the specific Permission entity
        $permission = $this->entityManager->getRepository(Permission::class)->createQueryBuilder('p')
            ->join('p.module', 'm')
            ->where('p.role = :role')
            ->andWhere('m.moduleKey = :key')
            ->setParameter('role', $role)
            ->setParameter('key', $moduleKey)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$permission) {
            return false; // No rule defined = Access Denied
        }

        // Check the specific flag
        return match ($action) {
            'view' => $permission->isCanView(),
            'create' => $permission->isCanCreate(),
            'edit' => $permission->isCanEdit(),
            'delete' => $permission->isCanDelete(),
            default => false,
        };
    }
}