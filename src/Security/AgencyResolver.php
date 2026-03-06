<?php

namespace App\Security;

use App\Entity\Agency;
use App\Entity\User;

class AgencyResolver
{
    public static function resolveAgency(object $entity): ?int
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

        // Linked entities owned via policy (Nominee, Rider, SurvivalBenefit, etc.)
        if (method_exists($entity, 'getPolicy') && $entity->getPolicy()?->getAgency()) {
            return $entity->getPolicy()->getAgency()->getId();
        }

        // Linked entities owned via client
        if (method_exists($entity, 'getClient') && $entity->getClient()?->getAgency()) {
            return $entity->getClient()->getAgency()->getId();
        }

        // Linked entities owned via role (Permission)
        if (method_exists($entity, 'getRole') && $entity->getRole()?->getAgency()) {
            return $entity->getRole()->getAgency()->getId();
        }

        return null; // Entity is global or has no agency link
    }
}