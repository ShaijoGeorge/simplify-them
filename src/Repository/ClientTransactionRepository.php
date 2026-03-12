<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Agency;
use App\Entity\ClientTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientTransaction>
 */
class ClientTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientTransaction::class);
    }

    /**
     * Returns the running balance for a client within an agency.
     *
     * Positive balance → client owes agency
     * Negative balance → agency owes client
     */
    public function getClientBalance(Client $client, Agency $agency): float
    {
        $transactions = $this->findBy(
            ['client' => $client, 'agency' => $agency],
            ['transactionDate' => 'ASC', 'id' => 'ASC']
        );

        $balance = 0.0;
        foreach ($transactions as $txn) {
            $balance += $txn->getSignedAmount();
        }

        return round($balance, 2);
    }

    /**
     * Returns all transactions for a client in an agency, ordered chronologically.
     *
     * @return ClientTransaction[]
     */
    public function getClientTransactions(Client $client, Agency $agency): array
    {
        return $this->findBy(
            ['client' => $client, 'agency' => $agency],
            ['transactionDate' => 'ASC', 'id' => 'ASC']
        );
    }

    /**
     * Returns all clients with non-zero balances for an agency.
     * Useful for a "Who owes whom?" overview.
     *
     * @return array<array{client: Client, balance: float}>
     */
    public function getOutstandingBalances(Agency $agency): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.client) AS clientId')
            ->addSelect("SUM(CASE 
                WHEN t.type = 'PAID_TO_LIC' THEN CAST(t.amount AS float)
                WHEN t.type = 'COLLECTION' THEN -CAST(t.amount AS float)
                WHEN t.type = 'SETTLEMENT' THEN -CAST(t.amount AS float)
                ELSE CAST(t.amount AS float)
            END) AS balance")
            ->where('t.agency = :agency')
            ->setParameter('agency', $agency)
            ->groupBy('t.client')
            ->having('ABS(SUM(CASE 
                WHEN t.type = \'PAID_TO_LIC\' THEN CAST(t.amount AS float)
                WHEN t.type = \'COLLECTION\' THEN -CAST(t.amount AS float)
                WHEN t.type = \'SETTLEMENT\' THEN -CAST(t.amount AS float)
                ELSE CAST(t.amount AS float)
            END)) > 0.01')
            ->getQuery()
            ->getResult();

        return $qb;
    }
}
