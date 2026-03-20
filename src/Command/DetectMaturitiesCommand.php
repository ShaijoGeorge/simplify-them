<?php

namespace App\Command;

use App\Entity\PolicyStatusLog;
use App\Repository\PolicyRepository;
use App\Service\WhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Detects policies that have reached their maturity date and transitions them to MATURED status.
 * Sends WhatsApp notification to each client.
 *
 * Cron suggestion: run daily  →  0 3 * * * php bin/console app:detect-maturities
 */
#[AsCommand(
    name: 'app:detect-maturities',
    description: 'Detects IN_FORCE or PAID_UP policies where maturity date has passed and marks them as MATURED.',
)]
class DetectMaturitiesCommand extends Command
{
    public function __construct(
        private PolicyRepository $policyRepository,
        private EntityManagerInterface $entityManager,
        private WhatsAppService $whatsAppService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Detecting Matured Policies');

        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        $policies = $this->policyRepository->createQueryBuilder('p')
            ->where('p.status IN (:statuses)')
            ->andWhere('p.maturityDate IS NOT NULL')
            ->andWhere('p.maturityDate <= :today')
            ->setParameter('statuses', ['IN_FORCE', 'PAID_UP'])
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        $maturedCount = 0;
        $skipped = 0;
        $rows = [];

        foreach ($policies as $policy) {
            $oldStatus = $policy->getStatus();
            $policy->setStatus('MATURED');
            $maturedCount++;

            // Log the transition
            $log = new PolicyStatusLog();
            $log->setPolicy($policy);
            $log->setOldStatus($oldStatus);
            $log->setNewStatus('MATURED');
            $log->setTriggeredBy('app:detect-maturities');
            $log->setReason(sprintf(
                'Policy maturity date %s has passed.',
                $policy->getMaturityDate()->format('d-M-Y')
            ));
            $this->entityManager->persist($log);

            $maturityDate = $policy->getMaturityDate()->format('d-M-Y');

            $io->text(sprintf(
                '  → Policy %s | Matured on %s',
                $policy->getPolicyNumber(),
                $maturityDate
            ));

            // Send WhatsApp notification
            $client = $policy->getClient();
            if (!$client || !$client->getMobile()) {
                $skipped++;
                continue;
            }

            $whatsappUrl = $this->whatsAppService->generateMaturityWhatsAppUrl(
                $client->getMobile(),
                $policy->getPolicyNumber(),
                $maturityDate
            );

            $this->whatsAppService->sendMaturityNotification(
                $client->getMobile(),
                $client->getName(),
                $policy->getPolicyNumber(),
                $maturityDate
            );

            $rows[] = [
                $client->getName(),
                $client->getMobile(),
                $policy->getPolicyNumber(),
                $maturityDate,
                $whatsappUrl,
            ];
        }

        $this->entityManager->flush();

        // Display summary table
        if (!empty($rows)) {
            $io->section('Maturity Notification Summary');
            $io->table(
                ['Client', 'Mobile', 'Policy No.', 'Maturity Date', 'WhatsApp Link'],
                $rows
            );
        }

        if ($skipped > 0) {
            $io->warning("Skipped $skipped notifications (missing client or mobile number).");
        }

        if ($maturedCount === 0) {
            $io->success('No new matured policies detected today.');
        } else {
            $io->success(sprintf('%d policy/policies marked as MATURED.', $maturedCount));
        }

        return Command::SUCCESS;
    }
}
