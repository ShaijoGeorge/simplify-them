<?php

namespace App\Command;

use App\Entity\SaRebate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-sa-rebates',
    description: 'Seeds the SA Rebate master table with LIC high-SA rebate bands',
)]
class SeedSaRebatesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // SA Rebate bands (per ₹1,000 Sum Assured)
        $bands = [
            ['min' => 0,       'max' => 199999,  'rebate' => 0],
            ['min' => 200000,  'max' => 499999,  'rebate' => 2],
            ['min' => 500000,  'max' => null,     'rebate' => 3],
        ];

        $repo = $this->entityManager->getRepository(SaRebate::class);
        $count = 0;

        $io->text('Checking SA Rebate bands...');

        foreach ($bands as $band) {
            // Check if a band with this min already exists
            $exists = $repo->findOneBy(['minSumAssured' => (string) $band['min']]);

            if (!$exists) {
                $rebate = new SaRebate();
                $rebate->setMinSumAssured((string) $band['min']);
                $rebate->setMaxSumAssured($band['max'] !== null ? (string) $band['max'] : null);
                $rebate->setRebatePerThousand((string) $band['rebate']);

                $this->entityManager->persist($rebate);
                $io->text(sprintf(
                    ' -> Scheduled insert: ₹%s – ₹%s → ₹%s/1000',
                    number_format($band['min']),
                    $band['max'] !== null ? number_format($band['max']) : '∞',
                    $band['rebate']
                ));
                $count++;
            } else {
                $io->text(sprintf(
                    ' -> Skipped: ₹%s band (already exists)',
                    number_format($band['min'])
                ));
            }
        }

        $this->entityManager->flush();

        if ($count > 0) {
            $io->success(sprintf('Successfully inserted %d SA Rebate band(s).', $count));
        } else {
            $io->info('No new bands were inserted. Database is up to date.');
        }

        return Command::SUCCESS;
    }
}
