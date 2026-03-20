<?php

namespace App\Command;

use App\Entity\PolicyStatusLog;
use App\Repository\PolicyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Detects policies whose Premium Paying Term has expired but policy term is still active.
 * These should transition to PAID_UP status with a reduced Sum Assured.
 *
 * This handles the PPT-expiry scenario (policyholder completed all required premiums
 * but policy term continues). The lapse-based Paid-Up transition is handled by
 * app:detect-lapses.
 *
 * Cron suggestion: run daily  →  0 2 * * * php bin/console app:detect-paid-up
 */
#[AsCommand(
    name: 'app:detect-paid-up',
    description: 'Detects policies whose Premium Paying Term has expired and transitions them to PAID_UP status with reduced Sum Assured.',
)]
class DetectPaidUpCommand extends Command
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
        $io->title('Detecting Paid-Up Policies (PPT Expiry)');

        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        /**
         * A LAPSED policy that has paid premiums for at least 3 full years
         * (LIC minimum vesting period) is eligible to be converted to Paid-Up
         * instead of fully lapsing, IF it has not yet matured.
         *
         * We also catch IN_FORCE policies where the PPT anniversary has passed -
         * meaning no more premiums are expected but the cover should continue.
         */
        $policies = $this->policyRepository->createQueryBuilder('p')
            ->where('p.status IN (:statuses)')
            ->andWhere('p.commencementDate IS NOT NULL')
            ->andWhere('p.premiumPayingTerm IS NOT NULL')
            ->andWhere('p.maturityDate IS NOT NULL')
            ->andWhere('p.maturityDate > :today')
            ->setParameter('statuses', ['IN_FORCE', 'LAPSED'])
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        $convertedCount = 0;

        foreach ($policies as $policy) {
            $doc = $policy->getCommencementDate();
            $ppt = (int) $policy->getPremiumPayingTerm();

            if (!$doc || $ppt <= 0) {
                continue;
            }

            // Date when PPT expires (DOC + PPT years)
            $pptExpiry = clone $doc;
            $pptExpiry->modify("+{$ppt} years");

            // Full years elapsed since DOC
            $yearsElapsed = (int) $doc->diff($today)->y;

            // LIC minimum vesting requirement: at least 3 annual premiums paid
            $minimumYearsPaid = min(3, $ppt);

            // Check: PPT has expired AND minimum years met AND policy hasn't already been set to PAID_UP
            if ($pptExpiry <= $today && $yearsElapsed >= $minimumYearsPaid) {
                $oldStatus = $policy->getStatus();
                $policy->setStatus('PAID_UP');
                $policy->calculatePaidUpSumAssured();
                $convertedCount++;

                // Log the transition
                $log = new PolicyStatusLog();
                $log->setPolicy($policy);
                $log->setOldStatus($oldStatus);
                $log->setNewStatus('PAID_UP');
                $log->setTriggeredBy('app:detect-paid-up');
                $log->setReason(sprintf(
                    'Premium Paying Term (%d years) expired on %s. %d years premiums paid. Paid-Up SA: ₹%s',
                    $ppt,
                    $pptExpiry->format('d-M-Y'),
                    $yearsElapsed,
                    number_format((float) $policy->getPaidUpSumAssured(), 2)
                ));
                $log->setPaidUpSumAssured($policy->getPaidUpSumAssured());
                $this->entityManager->persist($log);

                $io->text(sprintf(
                    '  → Policy %s | PPT expired %s | Paid-Up SA: ₹%s',
                    $policy->getPolicyNumber(),
                    $pptExpiry->format('d-M-Y'),
                    number_format((float) $policy->getPaidUpSumAssured(), 2)
                ));
            }
        }

        $this->entityManager->flush();

        if ($convertedCount === 0) {
            $io->success('No new Paid-Up policies detected today.');
        } else {
            $io->success(sprintf('%d policy/policies marked as PAID_UP with reduced Sum Assured stored.', $convertedCount));
        }

        return Command::SUCCESS;
    }
}