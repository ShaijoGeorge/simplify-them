<?php
// src/Command/DetectLapsesCommand.php

namespace App\Command;

use App\Repository\PolicyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:detect-lapses',
    description: 'Detects and updates lapsed policies based on FUP and grace periods.',
)]
class DetectLapsesCommand extends Command
{
    public function __construct(
        private PolicyRepository $policyRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Detecting Lapsed Policies');

        $policies = $this->policyRepository->createQueryBuilder('p')
            ->where('p.status != :lapsedStatus')
            ->andWhere('p.fup IS NOT NULL')
            ->setParameter('lapsedStatus', 'LAPSED')
            ->getQuery()
            ->getResult();

        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $lapsedCount = 0;

        foreach ($policies as $policy) {
            $fup = $policy->getFup();
            $mode = $policy->getPremiumMode();

            if (!$fup) {
                continue;
            }

            // Determine grace period based on premium payment mode
            $gracePeriodDays = match (strtoupper($mode)) {
                'YLY', 'YEARLY', 'HLY', 'HALF-YEARLY', 'QLY', 'QUARTERLY' => 30,
                'NACH', 'MLY', 'MONTHLY' => 15,
                default => 0,
            };

            if ($gracePeriodDays > 0) {
                $lapseDate = clone $fup;
                $lapseDate->modify("+$gracePeriodDays days");

                // If today is past the grace period
                if ($today > $lapseDate) {
                    $policy->setStatus('LAPSED');
                    $lapsedCount++;
                }
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Finished checking policies. %d policies marked as LAPSED.', $lapsedCount));

        return Command::SUCCESS;
    }
}