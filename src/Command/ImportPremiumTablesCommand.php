<?php

namespace App\Command;

use App\Entity\LicPlan;
use App\Entity\PremiumTable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-premium-tables',
    description: 'Imports LIC premium rate tables from a CSV file.',
)]
class ImportPremiumTablesCommand extends Command
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
        $premiumTableRepository = $this->entityManager->getRepository(PremiumTable::class);

        $csvPath = $this->projectDir . '/import/premium_tables.csv';

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
        $batchSize = 100;
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty lines
            if (empty($row[0])) continue;

            $tableNumber = trim($row[0]);
            $entryAge = (int) trim($row[1]);
            $policyTerm = (int) trim($row[2]);
            $annualPremiumPerThousand = trim($row[3]);

            // Validate numeric values
            if ($entryAge <= 0 || $policyTerm <= 0 || !is_numeric($annualPremiumPerThousand)) {
                $io->warning(sprintf(
                    'Invalid data at row - Table: %s, Age: %s, Term: %s, Rate: %s - skipping.',
                    $tableNumber, $row[1], $row[2], $row[3]
                ));
                $skippedCount++;
                continue;
            }

            // Look up the LIC plan by table number
            $licPlan = $planRepository->findOneBy(['tableNumber' => $tableNumber]);

            if (!$licPlan) {
                $io->warning(sprintf('LIC Plan with table number "%s" not found - skipping row.', $tableNumber));
                $skippedCount++;
                continue;
            }

            // Check if a premium rate already exists for this plan + age + term
            $premiumTable = $premiumTableRepository->findOneBy([
                'licPlan'    => $licPlan,
                'entryAge'   => $entryAge,
                'policyTerm' => $policyTerm,
            ]);

            if (!$premiumTable) {
                $premiumTable = new PremiumTable();
                $premiumTable->setLicPlan($licPlan);
                $premiumTable->setEntryAge($entryAge);
                $premiumTable->setPolicyTerm($policyTerm);
                $addedCount++;
            } else {
                $updatedCount++;
            }

            $premiumTable->setAnnualPremiumPerThousand($annualPremiumPerThousand);

            $this->entityManager->persist($premiumTable);

            // Batch flush for performance on large datasets
            $rowCount++;
            if ($rowCount % $batchSize === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $io->note(sprintf('Processed %d rows...', $rowCount));
            }
        }

        fclose($handle);

        // Final flush for remaining rows
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
