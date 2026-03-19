<?php

namespace App\Repository;

use App\Entity\CommandExecution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandExecutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandExecution::class);
    }

    public function getStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $row = $conn->executeQuery("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS success,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
                ROUND(AVG(duration_ms)) AS avg_duration_ms
            FROM command_execution
        ")->fetchAssociative();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'success' => (int) ($row['success'] ?? 0),
            'failed' => (int) ($row['failed'] ?? 0),
            'avgDurationMs' => (int) ($row['avg_duration_ms'] ?? 0),
        ];
    }

    /**
     * Returns the most recent execution for each command name.
     */
    public function getLastExecutionByCommand(): array
    {
        $results = $this->createQueryBuilder('ce')
            ->where('ce.id IN (
                SELECT MAX(ce2.id) FROM App\Entity\CommandExecution ce2
                GROUP BY ce2.commandName
            )')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($results as $exec) {
            $map[$exec->getCommandName()] = $exec;
        }

        return $map;
    }

    /**
     * Returns recent executions for a specific command.
     */
    public function getHistoryForCommand(string $commandName, int $limit = 20): array
    {
        return $this->createQueryBuilder('ce')
            ->where('ce.commandName = :name')
            ->setParameter('name', $commandName)
            ->orderBy('ce.executedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the last N executions across all commands.
     */
    public function getRecentExecutions(int $limit = 10): array
    {
        return $this->createQueryBuilder('ce')
            ->orderBy('ce.executedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
