<?php

namespace App\Command;

use App\Entity\Module;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:insert-modules',
    description: 'Inserts application modules into the database',
)]
class InsertModulesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $modules = [
            ['name' => 'Agencies', 'key' => 'agencies'],
            ['name' => 'Clients', 'key' => 'clients'],
            ['name' => 'Commission Rules', 'key' => 'commission_rules'],
            ['name' => 'Dashboard', 'key' => 'dashboard'], 
            ['name' => 'LIC Plans', 'key' => 'lic_plans'],
            ['name' => 'Modules', 'key' => 'modules'],
            ['name' => 'Nominees', 'key' => 'nominees'],
            ['name' => 'Permissions', 'key' => 'permissions'],
            ['name' => 'Plan Types', 'key' => 'plan_types'],
            ['name' => 'Policies', 'key' => 'policies'],
            ['name' => 'Policy Riders', 'key' => 'policy_riders'],
            ['name' => 'Premium Collection', 'key' => 'premium_collection'],
            ['name' => 'Roles', 'key' => 'roles'],
            ['name' => 'Users', 'key' => 'users'],
        ];

        $repo = $this->entityManager->getRepository(Module::class);
        $count = 0;

        $io->text('Checking for modules...');

        foreach ($modules as $data) {
            // Check if module already exists to prevent duplicates
            $exists = $repo->findOneBy(['moduleKey' => $data['key']]);

            if (!$exists) {
                $module = new Module();
                $module->setName($data['name']);
                $module->setModuleKey($data['key']);
                
                $this->entityManager->persist($module);
                $io->text(sprintf(" -> Scheduled insert: %s (%s)", $data['name'], $data['key']));
                $count++;
            } else {
                $io->text(sprintf(" -> Skipped: %s (Already exists)", $data['name']));
            }
        }

        $this->entityManager->flush();

        if ($count > 0) {
            $io->success(sprintf('Successfully inserted %d new modules.', $count));
        } else {
            $io->info('No new modules were inserted. Database is up to date.');
        }

        return Command::SUCCESS;
    }
}