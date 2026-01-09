<?php

namespace App\EventSubscriber;

use App\Security\AgencyResolver;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class EasyAdminAgencySubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeCrudActionEvent::class => 'onCrudAction',
        ];
    }

    public function onCrudAction(BeforeCrudActionEvent $event): void
    {
        // Get the Entity being accessed
        $context = $event->getAdminContext();
        if (!$context) return;

        $entityDto = $context->getEntity();
        if (!$entityDto) return;

        $entity = $entityDto->getInstance();

        // Skip checks on "Index" (List) and "New" pages (Entity is null or empty)
        // We handle List filtering separately in the QueryBuilder
        if (!$entity || $entityDto->getPrimaryKeyValue() === null) {
            return;
        }

        // Get current user
        $user = $this->security->getUser();
        if (!$user) return;

        // SUPER ADMIN BYPASS (Platform Owner sees all)
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }

        // RESOLVE OWNERSHIP
        $entityAgencyId = AgencyResolver::resolveAgency($entity);
        $userAgencyId = $user->getAgency()?->getId();

        // THE SECURITY CHECK
        // If the entity belongs to an Agency, AND it is NOT the user's agency... BLOCK IT.
        if ($entityAgencyId !== null && $entityAgencyId !== $userAgencyId) {
            throw new AccessDeniedException(
                sprintf('Access Denied: You do not own this %s.', $context->getEntity()->getName())
            );
        }
    }
}
