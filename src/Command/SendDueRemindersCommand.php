<?php

namespace App\Command;

use App\Repository\PolicyRepository;
use App\Service\WhatsAppService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-reminders',
    description: 'Finds policies due soon and generates WhatsApp reminder links',
)]
class SendDueRemindersCommand extends Command
{
    public function __construct(
        private PolicyRepository $policyRepository,
        private WhatsAppService $whatsAppService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Number of days ahead to check for due policies', 3)
            ->addOption('range', 'r', InputOption::VALUE_NONE, 'If set, find policies due within 1 to --days days (range mode)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        $rangeMode = $input->getOption('range');

        $io->title('🚀 Premium Reminder Report');

        if ($rangeMode) {
            $io->text("Looking for IN_FORCE policies due within the next <info>$days days</info>...");
            $policies = $this->findPoliciesDueInRange($days);
        } else {
            $targetDate = new \DateTime("+$days days");
            $formattedDate = $targetDate->format('d-M-Y');
            $io->text("Looking for IN_FORCE policies due on: <info>$formattedDate</info>");
            $policies = $this->policyRepository->findBy([
                'nextDueDate' => $targetDate,
                'status' => 'IN_FORCE'
            ]);
        }

        $count = count($policies);
        $io->text("Found <info>$count</info> policies.");

        if ($count === 0) {
            $io->success('No reminders to send today. 🎉');
            return Command::SUCCESS;
        }

        // Build table rows
        $rows = [];
        $skipped = 0;

        foreach ($policies as $policy) {
            $client = $policy->getClient();

            if (!$client || !$client->getMobile()) {
                $skipped++;
                continue;
            }

            $dueDate = $policy->getNextDueDate()?->format('d-M-Y') ?? 'N/A';
            $amount = $policy->getTotalPremium() ?? '0';

            $whatsappUrl = $this->whatsAppService->generateWhatsAppUrl(
                $client->getMobile(),
                $client->getName(),
                $policy->getPolicyNumber(),
                $dueDate,
                $amount
            );

            $rows[] = [
                $client->getName(),
                $client->getMobile(),
                $policy->getPolicyNumber(),
                $dueDate,
                '₹' . number_format((float) $amount, 2),
                $whatsappUrl
            ];

            // Also log via WhatsAppService
            $this->whatsAppService->sendPremiumReminder(
                $client->getMobile(),
                $client->getName(),
                $policy->getPolicyNumber(),
                $dueDate,
                $amount
            );
        }

        // Display summary table
        if (!empty($rows)) {
            $io->section('📋 Reminder Summary');
            $io->table(
                ['Client', 'Mobile', 'Policy No.', 'Due Date', 'Amount', 'WhatsApp Link'],
                $rows
            );
        }

        $sentCount = count($rows);
        if ($skipped > 0) {
            $io->warning("Skipped $skipped policies (missing client or mobile number).");
        }

        $io->success("Done! $sentCount reminders generated. Copy any WhatsApp link above and paste in your browser to send.");

        return Command::SUCCESS;
    }

    /**
     * Find policies due within a range (1 to $days days from now).
     */
    private function findPoliciesDueInRange(int $days): array
    {
        $from = new \DateTime('+1 day');
        $to = new \DateTime("+$days days");

        $qb = $this->policyRepository->createQueryBuilder('p')
            ->where('p.nextDueDate BETWEEN :from AND :to')
            ->andWhere('p.status = :status')
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'))
            ->setParameter('status', 'IN_FORCE')
            ->orderBy('p.nextDueDate', 'ASC');

        return $qb->getQuery()->getResult();
    }
}