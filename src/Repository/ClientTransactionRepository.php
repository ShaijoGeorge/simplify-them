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
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT t.client_id,
                   SUM(CASE
                       WHEN t.type = 'PAID_TO_LIC'  THEN  CAST(t.amount AS DECIMAL(10,2))
                       WHEN t.type = 'REFUND'       THEN  CAST(t.amount AS DECIMAL(10,2))
                       WHEN t.type = 'COLLECTION'   THEN -CAST(t.amount AS DECIMAL(10,2))
                       WHEN t.type = 'SETTLEMENT'   THEN -CAST(t.amount AS DECIMAL(10,2))
                       ELSE CAST(t.amount AS DECIMAL(10,2))
                   END) AS balance
              FROM client_transaction t
             WHERE t.agency_id = :agencyId
             GROUP BY t.client_id
            HAVING ABS(SUM(CASE
                       WHEN t.type = 'PAID_TO_LIC'  THEN  CAST(t.amount AS DECIMAL(10,2))
                       WHEN t.type = 'REFUND'       THEN  CAST(t.amount AS DECIMAL(10,2))
                       WHEN t.type = 'COLLECTION'   THEN -CAST(t.amount AS DECIMAL(10,2))
                       WHEN t.type = 'SETTLEMENT'   THEN -CAST(t.amount AS DECIMAL(10,2))
                       ELSE CAST(t.amount AS DECIMAL(10,2))
                   END)) > 0.01
            SQL;

        $rows = $conn->executeQuery($sql, ['agencyId' => $agency->getId()])->fetchAllAssociative();

        $em = $this->getEntityManager();
        $result = [];

        foreach ($rows as $row) {
            $client = $em->getRepository(Client::class)->find($row['client_id']);
            if ($client) {
                $result[] = [
                    'client'  => $client,
                    'balance' => round((float) $row['balance'], 2),
                ];
            }
        }

        return $result;
    }

    /**
     * Returns all transactions for a client in an agency with running balance.
     *
     * @return array<array{transaction: ClientTransaction, runningBalance: float}>
     */
    public function getClientLedger(Client $client, Agency $agency): array
    {
        $transactions = $this->findBy(
            ['client' => $client, 'agency' => $agency],
            ['transactionDate' => 'ASC', 'id' => 'ASC']
        );

        $ledger = [];
        $balance = 0.0;

        foreach ($transactions as $txn) {
            $balance += $txn->getSignedAmount();
            $ledger[] = [
                'transaction'    => $txn,
                'runningBalance' => round($balance, 2),
            ];
        }

        return $ledger;
    }
}
