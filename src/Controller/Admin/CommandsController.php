<?php

namespace App\Controller\Admin;

use App\Entity\CommandExecution;
use App\Repository\CommandExecutionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class CommandsController extends AbstractController
{
    public function __construct(
        private CommandExecutionRepository $executionRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    // Registry of commands available on the Commands page.

    private function getCommandRegistry(): array
    {
        return [
            [
                'name' => 'app:detect-maturities',
                'description' => 'Detects IN_FORCE or PAID_UP policies where maturity date has passed, marks them as MATURED, and sends WhatsApp notifications.',
                'cron' => '0 3 * * *',
                'cronHuman' => 'Daily at 3:00 AM',
                'category' => 'Policy Lifecycle',
            ],
            [
                'name' => 'app:detect-lapses',
                'description' => 'Detects policies past their FUP grace period. Marks them as LAPSED or PAID_UP (if 3+ years premiums paid). Logs all transitions to PolicyStatusLog.',
                'cron' => '0 2 * * *',
                'cronHuman' => 'Daily at 2:00 AM',
                'category' => 'Policy Lifecycle',
            ],
            [
                'name' => 'app:detect-paid-up',
                'description' => 'Detects policies whose Premium Paying Term has expired and transitions them to PAID_UP status with reduced Sum Assured.',
                'cron' => '0 2 * * *',
                'cronHuman' => 'Daily at 2:00 AM',
                'category' => 'Policy Lifecycle',
            ],
            [
                'name' => 'app:check-survival-benefits',
                'description' => 'Lists upcoming survival benefit payouts so agents can follow up with clients.',
                'cron' => '0 6 * * *',
                'cronHuman' => 'Daily at 6:00 AM',
                'category' => 'Notifications',
            ],
            [
                'name' => 'app:send-reminders',
                'description' => 'Finds policies with premiums due soon and generates WhatsApp reminder links.',
                'cron' => '0 7 * * *',
                'cronHuman' => 'Daily at 7:00 AM',
                'category' => 'Notifications',
            ],
        ];
    }

    #[Route('/admin/commands', name: 'admin_commands')]
    public function commands(): Response
    {
        $registry = $this->getCommandRegistry();

        // Group by category
        $grouped = [];
        foreach ($registry as $cmd) {
            $grouped[$cmd['category']][] = $cmd;
        }

        // Stats & history
        $stats = $this->executionRepository->getStats();
        $lastExecutions = $this->executionRepository->getLastExecutionByCommand();
        $recentActivity = $this->executionRepository->getRecentExecutions(10);

        return $this->render('Admin/commands/index.html.twig', [
            'grouped_commands' => $grouped,
            'stats' => $stats,
            'last_executions' => $lastExecutions,
            'recent_activity' => $recentActivity,
        ]);
    }

    #[Route('/admin/commands/run', name: 'admin_commands_run', methods: ['POST'])]
    public function runCommand(Request $request, KernelInterface $kernel): JsonResponse
    {
        $commandName = $request->request->get('command');

        // Validate against registry
        $allowed = array_column($this->getCommandRegistry(), 'name');
        if (!in_array($commandName, $allowed, true)) {
            return new JsonResponse(['success' => false, 'output' => 'Command not allowed.'], 403);
        }

        $execution = new CommandExecution();
        $execution->setCommandName($commandName);
        $execution->setStatus('running');

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(['command' => $commandName]);
        $output = new BufferedOutput();

        $startTime = microtime(true);

        try {
            $exitCode = $application->run($input, $output);
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $content = $output->fetch();

            $execution->setExitCode($exitCode);
            $execution->setDurationMs($durationMs);
            $execution->setOutput($content);
            $execution->setStatus($exitCode === 0 ? 'success' : 'failed');

            $this->entityManager->persist($execution);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => $exitCode === 0,
                'output' => $content,
                'durationMs' => $durationMs,
                'executedAt' => $execution->getExecutedAt()->format('d M Y, h:i A'),
            ]);
        } catch (\Exception $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            $execution->setExitCode(-1);
            $execution->setDurationMs($durationMs);
            $execution->setOutput('Error: ' . $e->getMessage());
            $execution->setStatus('failed');

            $this->entityManager->persist($execution);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => false,
                'output' => 'Error: ' . $e->getMessage(),
                'durationMs' => $durationMs,
                'executedAt' => $execution->getExecutedAt()->format('d M Y, h:i A'),
            ], 500);
        }
    }

    #[Route('/admin/commands/history/{commandName}', name: 'admin_commands_history', methods: ['GET'])]
    public function commandHistory(string $commandName): JsonResponse
    {
        // Validate against registry
        $allowed = array_column($this->getCommandRegistry(), 'name');
        if (!in_array($commandName, $allowed, true)) {
            return new JsonResponse(['error' => 'Command not found.'], 404);
        }

        $history = $this->executionRepository->getHistoryForCommand($commandName, 20);

        $rows = [];
        foreach ($history as $exec) {
            $rows[] = [
                'status' => $exec->getStatus(),
                'executedAt' => $exec->getExecutedAt()->format('d M Y, h:i A'),
                'durationMs' => $exec->getDurationMs(),
                'exitCode' => $exec->getExitCode(),
                'output' => $exec->getOutput(),
            ];
        }

        return new JsonResponse(['history' => $rows]);
    }
}
