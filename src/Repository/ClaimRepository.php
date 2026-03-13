<?php

namespace App\Repository;

use App\Entity\Claim;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Claim>
 */
class ClaimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Claim::class);
    }

    /**
     * Returns all claims for policies belonging to a given client,
     * ordered by claim date descending.
     *
     * @return Claim[]
     */
    public function findByClient(int $clientId): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.policy', 'p')
            ->join('p.client', 'cl')
            ->addSelect('p')
            ->where('cl.id = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('c.claimDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
