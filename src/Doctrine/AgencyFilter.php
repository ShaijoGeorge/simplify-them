<?php

namespace App\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class AgencyFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // Check if the entity has a relationship to 'Agency'
        if (!$targetEntity->hasAssociation('agency')) {
            return '';
        }

        // Do not filter the 'User' entity (otherwise login might fail during lookup)
        // We mainly want to filter Clients, Policies, etc.
        if ($targetEntity->getReflectionClass()->name === 'App\Entity\User') {
            return '';
        }

        try {
            // Get the current logged-in Agency ID
            $agencyId = $this->getParameter('agency_id');
        } catch (\InvalidArgumentException $e) {
            // If no ID is set (e.g. not logged in), apply no filter
            return '';
        }

        if (empty($agencyId)) {
            return '';
        }

        // Return the SQL constraint to filter by agency_id
        return sprintf('%s.agency_id = %s', $targetTableAlias, $agencyId);
    }
}