<?php

namespace App\Security;

use App\Entity\Agency;
use App\Entity\User;

class AgencyResolver
{
    public static function resolveAgency(object $agency): ?int
    {
        // If the entity IS the Agency, return its own ID
        if ($entity instanceof Agency) {
            return $entity->getId();
        }

        // If the entity is a User (Staff), return their Agency ID
        if ($entity instanceof User) {
            return $entity->getAgency()?->getId();
        }

        // For everything else (Client, Policy, Receipt), check getAgency()
        if (method_exists($entity, 'getAgency') && $entity->getAgency()) {
            return $entity->getAgency()->getId();
        }

        return null; // Entity is global or has no agency link
    }
}