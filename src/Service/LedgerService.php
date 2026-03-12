<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Agency;
use App\Entity\ClientTransaction;
use App\Entity\PremiumReceipt;
use App\Repository\ClientTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for managing the Agency–Client premium ledger.
 *
 * Balance convention:
 *   Positive → client owes the agency
 *   Negative → agency owes the client
 */
class LedgerService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ClientTransactionRepository $txnRepository,
    ) {}

    /**
     * Get the running balance for a client within an agency.
     */
    public function getClientBalance(Client $client, Agency $agency): float
    {
        return $this->txnRepository->getClientBalance($client, $agency);
    }

    /**
     * Auto-create a PAID_TO_LIC ledger entry when a PremiumReceipt is saved.
     * Links via receipt → policy → client.
     */
    public function recordPaidToLic(PremiumReceipt $receipt): ?ClientTransaction
    {
        $policy = $receipt->getPolicy();
        if (!$policy) {
            return null;
        }

        $client = $policy->getClient();
        $agency = $receipt->getAgency();

        if (!$client || !$agency) {
            return null;
        }

        $txn = new ClientTransaction();
        $txn->setClient($client);
        $txn->setAgency($agency);
        $txn->setPremiumReceipt($receipt);
        $txn->setType(ClientTransaction::TYPE_PAID_TO_LIC);
        $txn->setAmount($receipt->getAmount());
        $txn->setTransactionDate($receipt->getPaymentDate() ?? new \DateTime());
        $txn->setDescription(sprintf(
            'Premium paid to LIC — Receipt %s, Policy %s',
            $receipt->getReceiptNumber() ?? 'N/A',
            $policy->getPolicyNumber() ?? 'N/A'
        ));

        $this->em->persist($txn);

        // If amount was collected from client, also create a COLLECTION entry
        if ($receipt->getCollectedFromClient() !== null && (float) $receipt->getCollectedFromClient() > 0) {
            $collectionTxn = new ClientTransaction();
            $collectionTxn->setClient($client);
            $collectionTxn->setAgency($agency);
            $collectionTxn->setPremiumReceipt($receipt);
            $collectionTxn->setType(ClientTransaction::TYPE_COLLECTION);
            $collectionTxn->setAmount($receipt->getCollectedFromClient());
            $collectionTxn->setTransactionDate($receipt->getCollectionDate() ?? $receipt->getPaymentDate() ?? new \DateTime());
            $collectionTxn->setDescription(sprintf(
                'Collected from client — Receipt %s, Policy %s',
                $receipt->getReceiptNumber() ?? 'N/A',
                $policy->getPolicyNumber() ?? 'N/A'
            ));

            $this->em->persist($collectionTxn);
        }

        return $txn;
    }

    /**
     * Create a COLLECTION entry (agent collected money from client).
     */
    public function recordCollection(
        Client $client,
        Agency $agency,
        float $amount,
        \DateTime $date,
        ?PremiumReceipt $receipt = null,
        ?string $description = null
    ): ClientTransaction {
        $txn = new ClientTransaction();
        $txn->setClient($client);
        $txn->setAgency($agency);
        $txn->setPremiumReceipt($receipt);
        $txn->setType(ClientTransaction::TYPE_COLLECTION);
        $txn->setAmount((string) $amount);
        $txn->setTransactionDate($date);
        $txn->setDescription($description ?? 'Premium collected from client');

        $this->em->persist($txn);

        return $txn;
    }

    /**
     * Create a SETTLEMENT entry to close outstanding balances.
     */
    public function recordSettlement(
        Client $client,
        Agency $agency,
        float $amount,
        \DateTime $date,
        string $description
    ): ClientTransaction {
        $txn = new ClientTransaction();
        $txn->setClient($client);
        $txn->setAgency($agency);
        $txn->setType(ClientTransaction::TYPE_SETTLEMENT);
        $txn->setAmount((string) $amount);
        $txn->setTransactionDate($date);
        $txn->setDescription($description);

        $this->em->persist($txn);

        return $txn;
    }
}
