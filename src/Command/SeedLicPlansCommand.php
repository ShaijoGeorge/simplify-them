<?php

namespace App\Command;

use App\Entity\LicPlan;
use App\Entity\LicPlanType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-lic-plans',
    description: 'Seeds a comprehensive production-ready list of LIC Plans (Old & New) into the database.',
)]
class SeedLicPlansCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Seeding / Reconciling LIC Plans (Old & Active)');

        // 1. Fetch all Plan Types into an associative array for fast lookup
        $typeRepo = $this->entityManager->getRepository(LicPlanType::class);
        $types = $typeRepo->findAll();
        $typeMap = [];
        foreach ($types as $type) {
            $typeMap[strtoupper($type->getName())] = $type;
        }

        if (empty($typeMap)) {
            $io->error('No Plan Types found! Please run "php bin/console app:insert-plan-types" first.');
            return Command::FAILURE;
        }

        // 2. Comprehensive Data Array
        // Format: [Table No, Plan Name, Category Name, IsActive, Description]
        $licPlansDataset = [
            // ════════════════════════════════════════════════════════════════════════
            // 1. ACTIVE PLANS (Currently available for sale - Post 2024/2025 Updates)
            // ════════════════════════════════════════════════════════════════════════
            ['714', 'New Endowment Plan', 'Endowment', true, 'Updated 700-series regular premium participating endowment plan.'],
            ['715', 'New Jeevan Anand', 'Endowment', true, 'Updated 700-series participating whole life endowment plan.'],
            ['717', 'Single Premium Endowment', 'Single Premium', true, 'Updated 700-series lump-sum participating plan.'],
            ['720', 'New Money Back Plan - 20 Years', 'Money Back', true, 'Updated 700-series 20-year money back plan.'],
            ['721', 'New Money Back Plan - 25 Years', 'Money Back', true, 'Updated 700-series 25-year money back plan.'],
            ['732', 'New Children\'s Money Back Plan', 'Child Plan', true, 'Updated 700-series child money back plan.'],
            ['733', 'Jeevan Lakshya', 'Child Plan', true, 'Updated 700-series annual income benefit plan for family protection.'],
            ['734', 'Jeevan Tarun', 'Child Plan', true, 'Updated 700-series flexible survival benefit child plan.'],
            ['736', 'Jeevan Labh', 'Endowment', true, 'Updated 700-series limited premium paying endowment plan.'],
            ['745', 'Jeevan Umang', 'Whole Life', true, 'Updated 700-series whole life plan offering 8% guaranteed survival benefit.'],
            ['748', 'Bima Shree', 'Money Back', true, 'Updated 700-series money back plan for High Net-worth Individuals.'],
            ['749', 'Nivesh Plus', 'ULIP', true, 'Updated 700-series unit-linked single premium plan.'],
            ['752', 'SIIP', 'ULIP', true, 'Updated 700-series unit-linked regular premium plan.'],
            ['760', 'Bima Jyoti', 'Endowment', true, 'Updated 700-series non-participating plan with guaranteed additions.'],
            ['771', 'Jeevan Utsav', 'Whole Life', true, 'Whole life insurance plan with 10% guaranteed additions.'],
            ['774', 'Amritbaal', 'Child Plan', true, 'Child-focused savings plan with guaranteed additions.'],
            ['859', 'Saral Jeevan Bima', 'Term', true, 'Standard pure term life insurance plan mandated by IRDAI.'],
            ['867', 'New Pension Plus', 'Pension', true, 'Unit-linked, non-participating individual pension plan.'],
            ['873', 'Index Plus', 'ULIP', true, 'Unit-linked, regular premium life insurance plan.'],
            ['875', 'Yuva Term', 'Term', true, 'Offline pure risk premium term assurance plan for youth.'],
            ['876', 'Digi Term', 'Term', true, 'Online pure risk premium term assurance plan.'],
            ['877', 'Yuva Credit Life', 'Term', true, 'Offline decreasing term plan for loan protection.'],
            ['878', 'Digi Credit Life', 'Term', true, 'Online decreasing term plan for loan protection.'],
            ['879', 'Smart Pension', 'Pension', true, 'Non-linked, participating individual deferred annuity plan.'],
            ['881', 'Bima Lakshmi', 'Endowment', true, 'Endowment plan providing financial protection and savings.'],
            ['883', 'Jeevan Utsav Single Premium', 'Whole Life', true, 'Single premium version of the whole life Jeevan Utsav plan.'],
            ['887', 'Bima Kavach', 'Term', true, 'Standard term assurance plan.'],
            ['911', 'Nav Jeevan Shree - Single Premium', 'Endowment', true, 'Single premium participating endowment plan.'],
            ['912', 'Nav Jeevan Shree', 'Endowment', true, 'Regular premium participating endowment plan.'],
            ['954', 'New Tech-Term', 'Term', true, 'Updated online pure risk term plan (Replaces 854).'],
            ['955', 'New Jeevan Amar', 'Term', true, 'Updated offline pure risk term plan (Replaces 855).'],

            // ════════════════════════════════════════════════════════════════════════
            // 2. RECENTLY CLOSED PLANS (900/800 Series - Replaced by 700/870+ Series)
            // ════════════════════════════════════════════════════════════════════════
            ['914', 'New Endowment Plan (Old)', 'Endowment', false, '900-series participating endowment plan. Replaced by 714.'],
            ['915', 'New Jeevan Anand (Old)', 'Endowment', false, '900-series participating whole life endowment plan. Replaced by 715.'],
            ['916', 'New Bima Bachat (Old)', 'Money Back', false, '900-series single premium money back plan.'],
            ['917', 'Single Premium Endowment (Old)', 'Single Premium', false, '900-series lump-sum plan. Replaced by 717.'],
            ['920', 'New Money Back Plan - 20 Years (Old)', 'Money Back', false, '900-series 20-year money back plan. Replaced by 720.'],
            ['921', 'New Money Back Plan - 25 Years (Old)', 'Money Back', false, '900-series 25-year money back plan. Replaced by 721.'],
            ['932', 'New Children\'s Money Back Plan (Old)', 'Child Plan', false, '900-series child money back plan. Replaced by 732.'],
            ['933', 'Jeevan Lakshya (Old)', 'Child Plan', false, '900-series child future planning. Replaced by 733.'],
            ['934', 'Jeevan Tarun (Old)', 'Child Plan', false, '900-series flexible survival benefit child plan. Replaced by 734.'],
            ['936', 'Jeevan Labh (Old)', 'Endowment', false, '900-series limited premium endowment plan. Replaced by 736.'],
            ['943', 'Aadhaar Stambh', 'Micro Insurance', false, 'Exclusive endowment plan for male lives possessing Aadhaar.'],
            ['944', 'Aadhaar Shila', 'Micro Insurance', false, 'Exclusive endowment plan for female lives possessing Aadhaar.'],
            ['945', 'Jeevan Umang (Old)', 'Whole Life', false, '900-series whole life plan offering 8% guaranteed survival benefit. Replaced by 745.'],
            ['947', 'Jeevan Shiromani', 'Money Back', false, '900-series high net-worth money back plan with critical illness.'],
            ['948', 'Bima Shree (Old)', 'Money Back', false, '900-series money back plan for High Net-worth Individuals. Replaced by 748.'],
            ['849', 'Nivesh Plus (Old)', 'ULIP', false, '800-series unit-linked single premium plan. Replaced by 749.'],
            ['852', 'SIIP (Old)', 'ULIP', false, '800-series unit-linked regular premium plan. Replaced by 752.'],
            ['854', 'Tech Term (Old)', 'Term', false, 'Online pure risk premium protection plan. Replaced by 954.'],
            ['855', 'Jeevan Amar (Old)', 'Term', false, 'Non-linked pure risk protection life insurance plan. Replaced by 955.'],
            ['857', 'Jeevan Akshay - VII', 'Pension', false, 'Immediate annuity plan offering various options.'],
            ['858', 'New Jeevan Shanti', 'Pension', false, 'Single premium plan with deferred annuity options.'],
            ['869', 'Dhan Vridhhi', 'Single Premium', false, 'Closed single premium savings plan.'],
            ['874', 'Amritbaal (Old)', 'Child Plan', false, 'First iteration of Amritbaal, replaced by plan 774.'],

            // ════════════════════════════════════════════════════════════════════════
            // 3. OLD SERIES (800 Series: 2014 - 2020) - Closed but widely active
            // ════════════════════════════════════════════════════════════════════════
            ['812', 'New Jeevan Nidhi', 'Pension', false, 'Deferred annuity pension plan.'],
            ['814', 'New Endowment Plan (Older)', 'Endowment', false, 'Old 800-series version of New Endowment.'],
            ['815', 'New Jeevan Anand (Older)', 'Endowment', false, 'Old 800-series version of New Jeevan Anand.'],
            ['816', 'New Bima Bachat (Older)', 'Money Back', false, 'Old 800-series version of Bima Bachat.'],
            ['817', 'Single Premium Endowment (Older)', 'Single Premium', false, 'Old 800-series Single Premium Endowment.'],
            ['820', 'New Money Back - 20 Yrs (Older)', 'Money Back', false, 'Old 800-series 20-year Money Back.'],
            ['821', 'New Money Back - 25 Yrs (Older)', 'Money Back', false, 'Old 800-series 25-year Money Back.'],
            ['822', 'Anmol Jeevan II', 'Term', false, 'Pure term assurance policy.'],
            ['823', 'Amulya Jeevan II', 'Term', false, 'High sum assured pure term policy.'],
            ['826', 'Jeevan Tarun (Old Version)', 'Child Plan', false, 'Early version of Jeevan Tarun.'],
            ['827', 'Jeevan Rakshak', 'Micro Insurance', false, 'Regular premium participating micro-insurance plan.'],
            ['828', 'Varishtha Pension Bima Yojana', 'Pension', false, 'Subsidized pension scheme for senior citizens.'],
            ['830', 'Limited Premium Endowment', 'Endowment', false, 'Limited premium paying endowment plan.'],
            ['832', 'New Children\'s Money Back (Older)', 'Child Plan', false, 'Old 800-series version of Children\'s Money Back.'],
            ['833', 'Jeevan Lakshya (Older)', 'Child Plan', false, 'Old 800-series version of Jeevan Lakshya.'],
            ['834', 'Jeevan Tarun (Older)', 'Child Plan', false, 'Old 800-series version of Jeevan Tarun.'],
            ['836', 'Jeevan Labh (Older)', 'Endowment', false, 'Old 800-series version of Jeevan Labh.'],
            ['838', 'Jeevan Pragati', 'Endowment', false, 'Non-linked plan with increasing death benefit.'],
            ['841', 'Bima Diamond', 'Money Back', false, 'Money back plan with extended risk cover.'],
            ['843', 'Aadhaar Stambh (Older)', 'Micro Insurance', false, 'Old 800-series version of Aadhaar Stambh.'],
            ['844', 'Aadhaar Shila (Older)', 'Micro Insurance', false, 'Old 800-series version of Aadhaar Shila.'],
            ['845', 'Jeevan Umang (Older)', 'Whole Life', false, 'Old 800-series version of Jeevan Umang.'],
            ['847', 'Jeevan Shiromani (Older)', 'Money Back', false, 'High net-worth money back plan with critical illness.'],
            ['848', 'Bima Shree (Older)', 'Money Back', false, 'Money back plan for High Net-worth Individuals.'],
            ['850', 'Jeevan Shanti (Older)', 'Pension', false, 'Original Jeevan Shanti deferred/immediate annuity plan.'],
            ['856', 'Pradhan Mantri Vaya Vandana Yojana', 'Pension', false, 'Government subsidized pension plan (PMVVY).'],

            // ════════════════════════════════════════════════════════════════════════
            // 4. HISTORICAL PLANS (Pre-2014) - The True Classics
            // ════════════════════════════════════════════════════════════════════════
            ['14', 'Endowment Plan', 'Endowment', false, 'The classic LIC Endowment plan.'],
            ['48', 'Endowment Plan (Limited Payment)', 'Endowment', false, 'Classic Endowment Plan with limited premium payment.'],
            ['50', 'Children\'s Deferred Endowment', 'Child Plan', false, 'Classic deferred endowment for children.'],
            ['75', 'Money Back - 20 Yrs (Classic)', 'Money Back', false, 'Classic 20-year Money Back plan.'],
            ['88', 'Jeevan Mitra (Double Cover)', 'Endowment', false, 'Endowment plan with double death benefit.'],
            ['89', 'Jeevan Saathi', 'Endowment', false, 'Joint life endowment plan for couples.'],
            ['90', 'Marriage Endowment / Educational Annuity', 'Endowment', false, 'Classic endowment for education/marriage.'],
            ['91', 'New Janaraksha', 'Endowment', false, 'Endowment plan with extended risk cover on lapsed policies.'],
            ['93', 'Money Back - 25 Yrs (Classic)', 'Money Back', false, 'Classic 25-year Money Back plan.'],
            ['94', 'Jeevan Surabhi - 15 Yrs', 'Money Back', false, 'Money back plan with increasing life cover (15 Yrs).'],
            ['95', 'Jeevan Surabhi - 20 Yrs', 'Money Back', false, 'Money back plan with increasing life cover (20 Yrs).'],
            ['102', 'Jeevan Kishore', 'Child Plan', false, 'Child endowment plan.'],
            ['103', 'Jeevan Chhaya', 'Child Plan', false, 'Child plan ensuring higher education funds.'],
            ['106', 'Jeevan Surabhi - 25 Yrs', 'Money Back', false, 'Money back plan with increasing life cover (25 Yrs).'],
            ['109', 'Jeevan Vishwas', 'Endowment', false, 'Endowment plan tailored for handicapped dependents.'],
            ['111', 'Bima Kiran', 'Term', false, 'Term protection plan with return of premiums.'],
            ['112', 'Raj Vidyarthi', 'Child Plan', false, 'Classic child education plan.'],
            ['113', 'Jeevan Mangal', 'Rural', false, 'Micro-insurance term plan with return of premium.'],
            ['122', 'Bal Vidya', 'Child Plan', false, 'Single premium child education plan.'],
            ['133', 'Jeevan Mitra (Triple Cover)', 'Endowment', false, 'Endowment plan with triple death benefit.'],
            ['147', 'Jeevan Rekha', 'Whole Life', false, 'Whole life plan with money back features.'],
            ['149', 'Jeevan Anand (Classic)', 'Endowment', false, 'The blockbuster original Jeevan Anand (Table 149).'],
            ['150', 'New Bima Kiran', 'Term', false, 'Upgraded Bima Kiran protection plan.'],
            ['151', 'Jeevan Pramukh', 'Endowment', false, 'High sum assured endowment plan with guaranteed additions.'],
            ['152', 'New Jeevan Akshay I', 'Pension', false, 'Historic immediate annuity plan.'],
            ['153', 'Anmol Jeevan', 'Term', false, 'Classic term assurance plan.'],
            ['162', 'Jeevan Shree-I', 'Endowment', false, 'Endowment plan with Guaranteed Additions.'],
            ['164', 'Anmol Jeevan - 1', 'Term', false, 'Term insurance policy.'],
            ['165', 'Jeevan Tarang (Revised)', 'Whole Life', false, 'Updated version of Jeevan Tarang.'],
            ['168', 'Jeevan Anurag', 'Child Plan', false, 'Child plan funding educational needs at pre-determined intervals.'],
            ['169', 'Jeevan Saral', 'Endowment', false, 'Highly popular flexible premium endowment plan (Table 169).'],
            ['171', 'Sudarshan', 'Endowment', false, 'Historic endowment plan.'],
            ['174', 'Bima Gold', 'Money Back', false, 'The original Bima Gold money back plan.'],
            ['175', 'Bima Bachat', 'Money Back', false, 'Original single premium money back plan.'],
            ['177', 'Jeevan Bharati', 'Endowment', false, 'Exclusive endowment plan for women.'],
            ['178', 'Jeevan Tarang', 'Whole Life', false, 'Whole life plan providing annual survival benefits (Table 178).'],
            ['179', 'New Bima Gold', 'Money Back', false, 'New Bima Gold policy offering survival benefits and extended cover (Table 179).'],
            ['184', 'Child Career Plan', 'Child Plan', false, 'Plan meeting the increasing educational needs of growing children.'],
            ['186', 'Jeevan Amrit', 'Endowment', false, 'Premium return plan.'],
            ['189', 'Jeevan Akshay VI', 'Pension', false, 'Immediate annuity plan (Akshay series VI - Table 189).'],
            ['190', 'Jeevan Ankur', 'Child Plan', false, 'Child plan with income benefit to the family on parent\'s death.'],
            ['193', 'Jeevan Nischay', 'Single Premium', false, 'Highly popular single premium endowment plan.'],
            ['199', 'Jeevan Aastha', 'Single Premium', false, 'Guaranteed single premium endowment plan.'],

            // ════════════════════════════════════════════════════════════════════════
            // 5. OLD ULIP PLANS (Unit Linked)
            // ════════════════════════════════════════════════════════════════════════
            ['172', 'Bima Plus', 'ULIP', false, 'Early Unit Linked Insurance Plan.'],
            ['173', 'Future Plus', 'ULIP', false, 'Unit linked plan.'],
            ['180', 'Money Plus', 'ULIP', false, 'Unit linked money back plan.'],
            ['181', 'Market Plus', 'ULIP', false, 'Unit linked pension plan.'],
            ['187', 'Fortune Plus', 'ULIP', false, 'Unit linked premium plan.'],
            ['188', 'Profit Plus', 'ULIP', false, 'Unit linked endowment plan.'],
            ['191', 'Market Plus I', 'ULIP', false, 'Unit linked pension plan.'],
            ['192', 'Money Plus I', 'ULIP', false, 'Unit linked money back plan.'],
            ['801', 'Endowment Plus', 'ULIP', false, 'Unit linked endowment plan.'],
            ['802', 'Bima Account 1', 'ULIP', false, 'Variable insurance plan.'],
            ['803', 'Bima Account 2', 'ULIP', false, 'Variable insurance plan for higher premiums.'],
            ['835', 'New Endowment Plus', 'ULIP', false, '800-series Unit Linked Endowment.'],
        ];

        $planRepo = $this->entityManager->getRepository(LicPlan::class);
        
        $inserted = 0;
        $updated = 0;
        $skippedCount = 0;

        foreach ($licPlansDataset as $data) {
            $tableNo = $data[0];
            $planName = $data[1];
            $categoryName = strtoupper($data[2]);
            $isActive = $data[3];
            $desc = $data[4];

            // Resolve the PlanType Entity
            $planType = $typeMap[$categoryName] ?? null;

            if (!$planType) {
                $io->warning("Plan Type '{$data[2]}' not found for Table {$tableNo}. Skipping.");
                continue;
            }

            // Check if Plan already exists by Table Number
            $plan = $planRepo->findOneBy(['tableNumber' => $tableNo]);

            if ($plan) {
                // Determine if an update is needed (we don't touch names if they manually changed them, but we sync type and status)
                $needsUpdate = false;
                
                if ($plan->getPlanType() !== $planType) {
                    $plan->setPlanType($planType);
                    $needsUpdate = true;
                }
                if ($plan->isActive() !== $isActive) {
                    $plan->setIsActive($isActive);
                    $needsUpdate = true;
                }
                
                // Only update description if it's currently empty
                if (empty($plan->getDescription())) {
                    $plan->setDescription($desc);
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $updated++;
                    $io->text("  <comment>UPDATED</comment> Table {$tableNo} - {$planName}");
                } else {
                    $skippedCount++;
                }
            } else {
                // Insert New Plan
                $plan = new LicPlan();
                $plan->setTableNumber($tableNo);
                $plan->setPlanName($planName);
                $plan->setPlanType($planType);
                $plan->setIsActive($isActive);
                $plan->setDescription($desc);

                $this->entityManager->persist($plan);
                $inserted++;
                $io->text("  <info>INSERTED</info> Table {$tableNo} - {$planName}");
            }
        }

        // Flush to database
        $this->entityManager->flush();

        $io->newLine();
        $io->success(sprintf(
            'Seeding Complete! Inserted: %d | Updated: %d | Skipped (No changes): %d', 
            $inserted, 
            $updated, 
            $skippedCount
        ));

        return Command::SUCCESS;
    }
}