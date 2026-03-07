<?php

namespace App\Command;

use App\Entity\BonusRate;
use App\Entity\LicPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-bonus-rates',
    description: 'Imports annual bonus rate declarations from a CSV file.',
)]
class ImportBonusRatesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')] private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $planRepository = $this->entityManager->getRepository(LicPlan::class);
        $bonusRateRepository = $this->entityManager->getRepository(BonusRate::class);

        $csvPath = $this->projectDir . '/import/bonus_rates.csv';

        if (!file_exists($csvPath)) {
            $io->error("CSV file not found at: " . $csvPath);
            return Command::FAILURE;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $io->error("Could not open the CSV file.");
            return Command::FAILURE;
        }

        // Read and skip the header row
        $header = fgetcsv($handle);

        $addedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty lines
            if (empty($row[0])) continue;

            $tableNumber = trim($row[0]);
            $financialYear = trim($row[1]);
            $simpleReversionaryBonus = trim($row[2]);
            $finalAdditionalBonus = trim($row[3] ?? '');
            $loyaltyAddition = trim($row[4] ?? '');

            // Look up the LIC plan by table number
            $licPlan = $planRepository->findOneBy(['tableNumber' => $tableNumber]);

            if (!$licPlan) {
                $io->warning(sprintf('LIC Plan with table number "%s" not found - skipping row.', $tableNumber));
                $skippedCount++;
                continue;
            }

            // Check if a bonus rate already exists for this plan + year
            $bonusRate = $bonusRateRepository->findOneBy([
                'licPlan' => $licPlan,
                'financialYear' => $financialYear,
            ]);

            if (!$bonusRate) {
                $bonusRate = new BonusRate();
                $bonusRate->setLicPlan($licPlan);
                $bonusRate->setFinancialYear($financialYear);
                $addedCount++;
            } else {
                $updatedCount++;
            }

            $bonusRate->setSimpleReversionaryBonus($simpleReversionaryBonus);
            $bonusRate->setFinalAdditionalBonus($finalAdditionalBonus !== '' ? $finalAdditionalBonus : null);
            $bonusRate->setLoyaltyAddition($loyaltyAddition !== '' ? $loyaltyAddition : null);

            $this->entityManager->persist($bonusRate);
        }

        fclose($handle);

        $this->entityManager->flush();

        $io->success(sprintf(
            'Import complete! %d new rates added, %d existing rates updated, %d rows skipped.',
            $addedCount,
            $updatedCount,
            $skippedCount
        ));

        return Command::SUCCESS;
    }
}
