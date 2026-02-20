<?php

namespace App\Command;

use App\Entity\LicPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-lic-plans',
    description: 'Imports LIC plans from a CSV file.',
)]
class ImportLicPlansCommand extends Command
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
        
        $csvPath = $this->projectDir . '/import/lic_plans.csv';

        if (!file_exists($csvPath)) {
            $io->error("CSV file not found at: " . $csvPath);
            return Command::FAILURE;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $io->error("Could not open the CSV file.");
            return Command::FAILURE;
        }

        // Read the header row and skip it
        $header = fgetcsv($handle);

        $addedCount = 0;
        $updatedCount = 0;

        // Read line by line
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty lines
            if (empty($row[0])) continue;

            $tableNumber = trim($row[0]);
            $planName = trim($row[1]);
            $isActive = (bool) trim($row[2]);

            $plan = $planRepository->findOneBy(['tableNumber' => $tableNumber]);

            if (!$plan) {
                $plan = new LicPlan();
                $plan->setTableNumber($tableNumber);
                $addedCount++;
            } else {
                $updatedCount++;
            }

            $plan->setPlanName($planName);
            $plan->setIsActive($isActive);
            
            $this->entityManager->persist($plan);
        }

        fclose($handle);

        // Flush all changes to the database
        $this->entityManager->flush();

        $io->success(sprintf('Successfully imported! %d new plans added, %d existing plans updated.', $addedCount, $updatedCount));

        return Command::SUCCESS;
    }
}