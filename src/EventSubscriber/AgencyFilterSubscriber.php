<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AgencyFilterSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private EntityManagerInterface $em;

    public function __construct(Security $security, EntityManagerInterface $em) {
        $this->security = $security;
        $this->em = $em;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Get the current user
        $user = $this->security->getUser();

        // If user is logged in AND belongs to an Agency
        if ($user instanceof User && $user->getAgency()) {
            // Enable the filter
            if ($this->em->getFilters()->has('agency_filter')) {
                $filter = $this->em->getFilters()->enable('agency_filter');
                
                // Pass the Agency ID to the filter
                $filter->setParameter('agency_id', $user->getAgency()->getId());
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}