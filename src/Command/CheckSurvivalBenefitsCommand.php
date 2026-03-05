<?php

namespace App\Command;

use App\Repository\SurvivalBenefitRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-survival-benefits',
    description: 'Lists upcoming survival benefit payouts so agents can follow up with clients',
)]
class CheckSurvivalBenefitsCommand extends Command
{
    public function __construct(
        private SurvivalBenefitRepository $survivalBenefitRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'days',
            'd',
            InputOption::VALUE_OPTIONAL,
            'Number of days ahead to look for upcoming benefits',
            30
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');

        $io->title("🔔 Upcoming Survival Benefit Payouts (next {$days} days)");

        $benefits = $this->survivalBenefitRepository->findUpcomingBenefits($days);
        $count = count($benefits);

        if ($count === 0) {
            $io->success('No upcoming survival benefit payouts found.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($benefits as $benefit) {
            $policy = $benefit->getPolicy();
            $client = $policy?->getClient();

            $rows[] = [
                $policy?->getPolicyNumber() ?? '-',
                $client?->getName() ?? '-',
                $benefit->getDueDate()?->format('d-M-Y') ?? '-',
                '₹' . number_format($benefit->getAmountFloat(), 2),
                $benefit->getPercentageOfSA() . '%',
                $benefit->getStatus(),
            ];
        }

        $io->table(
            ['Policy #', 'Client', 'Due Date', 'Amount', '% of SA', 'Status'],
            $rows,
        );

        $io->success("Found {$count} upcoming survival benefit payout(s).");

        return Command::SUCCESS;
    }
}
