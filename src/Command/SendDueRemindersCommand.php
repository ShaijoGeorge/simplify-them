<?php

namespace App\Command;

use App\Entity\Policy;
use App\Repository\PolicyRepository;
use App\Service\WhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-reminders',
    description: 'Finds policies due soon and sends WhatsApp reminders',
)]
class SendDueRemindersCommand extends Command
{
    public function __construct(
        private PolicyRepository $policyRepository,
        private WhatsAppService $whatsAppService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('ðŸš€ Starting Premium Reminder Job...');

        // Define the target date (e.g., Remind 3 days before due date)
        $targetDate = new \DateTime('+3 days'); 
        $formattedDate = $targetDate->format('Y-m-d');
        
        $io->text("Looking for policies due on: " . $formattedDate);

        // Query Policies
        // Note: You might need to add a custom query in PolicyRepository to ignore "Lapsed" or "Matured" policies
        $policies = $this->policyRepository->findBy([
            'nextDueDate' => $targetDate,
            'status' => 'IN_FORCE' // Only remind active clients
        ]);

        $count = count($policies);
        $io->text("Found $count policies.");

        if ($count === 0) {
            $io->success('No reminders to send today.');
            return Command::SUCCESS;
        }

        // Loop and Send
        $sentCount = 0;
        foreach ($policies as $policy) {
            $client = $policy->getClient();
            $agency = $policy->getAgency();

            // Check if Agency is Active (SaaS Check)
            // if (!$agency->isActive()) continue; 

            if ($client && $client->getMobile()) {
                $io->text("-> Sending to {$client->getFirstName()} ({$policy->getPolicyNumber()})...");
                
                $this->whatsAppService->sendPremiumReminder(
                    $client->getMobile(),
                    $client->getFirstName(),
                    $policy->getPolicyNumber(),
                    $policy->getNextDueDate()->format('d-M-Y'),
                    $policy->getTotalPremium()
                );
                
                $sentCount++;
            }
        }

        $io->success("Job Complete! Sent $sentCount reminders.");
        return Command::SUCCESS;
    }
}