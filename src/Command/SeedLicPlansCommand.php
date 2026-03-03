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
    name: 'app:seed-lic-plans', // docker compose exec web php bin/console app:seed-lic-plans
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

        // 2. Dataset
        // Format: [Table No, Plan Name, Category, IsActive, Description, IsSinglePremium, IsLimitedPremium]
        //
        // IsSinglePremium  = true  → one-time lump-sum premium; drives 1.25% GST + 2% commission
        // IsLimitedPremium = true  → PPT < PolicyTerm by design (informational UI hint)
        $licPlansDataset = [
            // ════════════════════════════════════════════════════════════════════════
            // 1. ACTIVE PLANS (Currently available for sale - Post 2024/2025 Updates)
            // ════════════════════════════════════════════════════════════════════════
            ['714', 'New Endowment Plan', 'Endowment', true, 'Updated 700-series regular premium participating endowment plan.', false, false],
            ['715', 'New Jeevan Anand', 'Endowment', true, 'Updated 700-series participating whole life endowment plan.', false, false],
            ['717', 'Single Premium Endowment', 'Single Premium', true, 'Updated 700-series lump-sum participating plan.', true, false],
            ['720', 'New Money Back Plan - 20 Years', 'Money Back', true, 'Updated 700-series 20-year money back plan.', false, true],
            ['721', 'New Money Back Plan - 25 Years', 'Money Back', true, 'Updated 700-series 25-year money back plan.', false, true],
            ['732', 'New Children\'s Money Back Plan', 'Child Plan', true, 'Updated 700-series child money back plan.', false, true],
            ['733', 'Jeevan Lakshya', 'Child Plan', true, 'Updated 700-series annual income benefit plan for family protection.', false, true],
            ['734', 'Jeevan Tarun', 'Child Plan', true, 'Updated 700-series flexible survival benefit child plan.', false, true],
            ['736', 'Jeevan Labh', 'Endowment', true, 'Updated 700-series limited premium paying endowment plan.', false, true],
            ['745', 'Jeevan Umang', 'Whole Life', true, 'Updated 700-series whole life plan offering 8% guaranteed survival benefit.', false, true],
            ['748', 'Bima Shree', 'Money Back', true, 'Updated 700-series money back plan for High Net-worth Individuals.', false, true],
            ['749', 'Nivesh Plus', 'ULIP', true, 'Updated 700-series unit-linked single premium plan.', true, false],
            ['752', 'SIIP', 'ULIP', true, 'Updated 700-series unit-linked regular premium plan.', false, false],
            ['760', 'Bima Jyoti', 'Endowment', true, 'Updated 700-series non-participating plan with guaranteed additions.', false, true],
            ['771', 'Jeevan Utsav', 'Whole Life', true, 'Whole life insurance plan with 10% guaranteed additions.', false, true],
            ['774', 'Amritbaal', 'Child Plan', true, 'Child-focused savings plan with guaranteed additions.', false, true],
            ['859', 'Saral Jeevan Bima', 'Term', true, 'Standard pure term life insurance plan mandated by IRDAI.', false, false],
            ['867', 'New Pension Plus', 'Pension', true, 'Unit-linked, non-participating individual pension plan.', false, false],
            ['873', 'Index Plus', 'ULIP', true, 'Unit-linked, regular premium life insurance plan.', false, false],
            ['875', 'Yuva Term', 'Term', true, 'Offline pure risk premium term assurance plan for youth.', false, false],
            ['876', 'Digi Term', 'Term', true, 'Online pure risk premium term assurance plan.', false, false],
            ['877', 'Yuva Credit Life', 'Term', true, 'Offline decreasing term plan for loan protection.', false, false],
            ['878', 'Digi Credit Life', 'Term', true, 'Online decreasing term plan for loan protection.', false, false],
            ['879', 'Smart Pension', 'Pension', true, 'Non-linked, participating individual deferred annuity plan.', false, false],
            ['881', 'Bima Lakshmi', 'Endowment', true, 'Endowment plan providing financial protection and savings.', false, true],
            ['883', 'Jeevan Utsav Single Premium', 'Whole Life', true, 'Single premium version of the whole life Jeevan Utsav plan.', true, false],
            ['887', 'Bima Kavach', 'Term', true, 'Standard term assurance plan.', false, false],
            ['911', 'Nav Jeevan Shree - Single Premium', 'Endowment', true, 'Single premium participating endowment plan.', true, false],
            ['912', 'Nav Jeevan Shree', 'Endowment', true, 'Regular premium participating endowment plan.', false, false],
            ['954', 'New Tech-Term', 'Term', true, 'Updated online pure risk term plan (Replaces 854).', false, false],
            ['955', 'New Jeevan Amar', 'Term', true, 'Updated offline pure risk term plan (Replaces 855).', false, false],

            // ════════════════════════════════════════════════════════════════════════
            // 2. RECENTLY CLOSED PLANS (900/800 Series - Replaced by 700/870+ Series)
            // ════════════════════════════════════════════════════════════════════════
            ['914', 'New Endowment Plan (Old)', 'Endowment', false, '900-series participating endowment plan. Replaced by 714.', false, false],
            ['915', 'New Jeevan Anand (Old)', 'Endowment', false, '900-series participating whole life endowment plan. Replaced by 715.', false, false],
            ['916', 'New Bima Bachat (Old)', 'Money Back', false, '900-series single premium money back plan.', true, false],
            ['917', 'Single Premium Endowment (Old)', 'Single Premium', false, '900-series lump-sum plan. Replaced by 717.', true, false],
            ['920', 'New Money Back Plan - 20 Years (Old)', 'Money Back', false, '900-series 20-year money back plan. Replaced by 720.', false, true],
            ['921', 'New Money Back Plan - 25 Years (Old)', 'Money Back', false, '900-series 25-year money back plan. Replaced by 721.', false, true],
            ['932', 'New Children\'s Money Back Plan (Old)', 'Child Plan', false, '900-series child money back plan. Replaced by 732.', false, true],
            ['933', 'Jeevan Lakshya (Old)', 'Child Plan', false, '900-series child future planning. Replaced by 733.', false, true],
            ['934', 'Jeevan Tarun (Old)', 'Child Plan', false, '900-series flexible survival benefit child plan. Replaced by 734.', false, true],
            ['936', 'Jeevan Labh (Old)', 'Endowment', false, '900-series limited premium endowment plan. Replaced by 736.', false, true],
            ['943', 'Aadhaar Stambh', 'Micro Insurance', false, 'Exclusive endowment plan for male lives possessing Aadhaar.', false, false],
            ['944', 'Aadhaar Shila', 'Micro Insurance', false, 'Exclusive endowment plan for female lives possessing Aadhaar.', false, false],
            ['945', 'Jeevan Umang (Old)', 'Whole Life', false, '900-series whole life plan offering 8% guaranteed survival benefit. Replaced by 745.', false, true],
            ['947', 'Jeevan Shiromani', 'Money Back', false, '900-series high net-worth money back plan with critical illness.', false, true],
            ['948', 'Bima Shree (Old)', 'Money Back', false, '900-series money back plan for High Net-worth Individuals. Replaced by 748.', false, true],
            ['849', 'Nivesh Plus (Old)', 'ULIP', false, '800-series unit-linked single premium plan. Replaced by 749.', true, false],
            ['852', 'SIIP (Old)', 'ULIP', false, '800-series unit-linked regular premium plan. Replaced by 752.', false, false],
            ['854', 'Tech Term (Old)', 'Term', false, 'Online pure risk premium protection plan. Replaced by 954.', false, false],
            ['855', 'Jeevan Amar (Old)', 'Term', false, 'Non-linked pure risk protection life insurance plan. Replaced by 955.', false, false],
            ['857', 'Jeevan Akshay - VII', 'Pension', false, 'Immediate annuity plan offering various options.', true, false],
            ['858', 'New Jeevan Shanti', 'Pension', false, 'Single premium plan with deferred annuity options.', true, false],
            ['869', 'Dhan Vridhhi', 'Single Premium', false, 'Closed single premium savings plan.', true, false],
            ['874', 'Amritbaal (Old)', 'Child Plan', false, 'First iteration of Amritbaal, replaced by plan 774.', false, true],

            // ════════════════════════════════════════════════════════════════════════
            // 3. OLD SERIES (800 Series: 2014 - 2020) - Closed but widely active
            // ════════════════════════════════════════════════════════════════════════
            ['812', 'New Jeevan Nidhi', 'Pension', false, 'Deferred annuity pension plan.', false, false],
            ['814', 'New Endowment Plan (Older)', 'Endowment', false, 'Old 800-series version of New Endowment.', false, false],
            ['815', 'New Jeevan Anand (Older)', 'Endowment', false, 'Old 800-series version of New Jeevan Anand.', false, false],
            ['816', 'New Bima Bachat (Older)', 'Money Back', false, 'Old 800-series version of Bima Bachat.', true, false],
            ['817', 'Single Premium Endowment (Older)', 'Single Premium', false, 'Old 800-series Single Premium Endowment.', true, false],
            ['820', 'New Money Back - 20 Yrs (Older)', 'Money Back', false, 'Old 800-series 20-year Money Back.', false, true],
            ['821', 'New Money Back - 25 Yrs (Older)', 'Money Back', false, 'Old 800-series 25-year Money Back.', false, true],
            ['822', 'Anmol Jeevan II', 'Term', false, 'Pure term assurance policy.', false, false],
            ['823', 'Amulya Jeevan II', 'Term', false, 'High sum assured pure term policy.', false, false],
            ['826', 'Jeevan Tarun (Old Version)', 'Child Plan', false, 'Early version of Jeevan Tarun.', false, true],
            ['827', 'Jeevan Rakshak', 'Micro Insurance', false, 'Regular premium participating micro-insurance plan.', false, false],
            ['828', 'Varishtha Pension Bima Yojana', 'Pension', false, 'Subsidized pension scheme for senior citizens.', true, false],
            ['830', 'Limited Premium Endowment', 'Endowment', false, 'Limited premium paying endowment plan.', false, true],
            ['832', 'New Children\'s Money Back (Older)', 'Child Plan', false, 'Old 800-series version of Children\'s Money Back.', false, true],
            ['833', 'Jeevan Lakshya (Older)', 'Child Plan', false, 'Old 800-series version of Jeevan Lakshya.', false, true],
            ['834', 'Jeevan Tarun (Older)', 'Child Plan', false, 'Old 800-series version of Jeevan Tarun.', false, true],
            ['836', 'Jeevan Labh (Older)', 'Endowment', false, 'Old 800-series version of Jeevan Labh.', false, true],
            ['838', 'Jeevan Pragati', 'Endowment', false, 'Non-linked plan with increasing death benefit.', false, false],
            ['841', 'Bima Diamond', 'Money Back', false, 'Money back plan with extended risk cover.', false, true],
            ['843', 'Aadhaar Stambh (Older)', 'Micro Insurance', false, 'Old 800-series version of Aadhaar Stambh.', false, false],
            ['844', 'Aadhaar Shila (Older)', 'Micro Insurance', false, 'Old 800-series version of Aadhaar Shila.', false, false],
            ['845', 'Jeevan Umang (Older)', 'Whole Life', false, 'Old 800-series version of Jeevan Umang.', false, true],
            ['847', 'Jeevan Shiromani (Older)', 'Money Back', false, 'High net-worth money back plan with critical illness.', false, true],
            ['848', 'Bima Shree (Older)', 'Money Back', false, 'Money back plan for High Net-worth Individuals.', false, true],
            ['850', 'Jeevan Shanti (Older)', 'Pension', false, 'Original Jeevan Shanti deferred/immediate annuity plan.', true, false],
            ['856', 'Pradhan Mantri Vaya Vandana Yojana', 'Pension', false, 'Government subsidized pension plan (PMVVY).', true, false],

            // ════════════════════════════════════════════════════════════════════════
            // 4. HISTORICAL PLANS (Pre-2014) - The True Classics
            // ════════════════════════════════════════════════════════════════════════
            ['14', 'Endowment Plan', 'Endowment', false, 'The classic LIC Endowment plan.', false, false],
            ['48', 'Endowment Plan (Limited Payment)', 'Endowment', false, 'Classic Endowment Plan with limited premium payment.', false, true],
            ['50', 'Children\'s Deferred Endowment', 'Child Plan', false, 'Classic deferred endowment for children.', false, false],
            ['75', 'Money Back - 20 Yrs (Classic)', 'Money Back', false, 'Classic 20-year Money Back plan.', false, true],
            ['88', 'Jeevan Mitra (Double Cover)', 'Endowment', false, 'Endowment plan with double death benefit.', false, false],
            ['89', 'Jeevan Saathi', 'Endowment', false, 'Joint life endowment plan for couples.', false, false],
            ['90', 'Marriage Endowment / Educational Annuity', 'Endowment', false, 'Classic endowment for education/marriage.', false, false],
            ['91', 'New Janaraksha', 'Endowment', false, 'Endowment plan with extended risk cover on lapsed policies.', false, false],
            ['93', 'Money Back - 25 Yrs (Classic)', 'Money Back', false, 'Classic 25-year Money Back plan.', false, true],
            ['94', 'Jeevan Surabhi - 15 Yrs', 'Money Back', false, 'Money back plan with increasing life cover (15 Yrs).', false, true],
            ['95', 'Jeevan Surabhi - 20 Yrs', 'Money Back', false, 'Money back plan with increasing life cover (20 Yrs).', false, true],
            ['102', 'Jeevan Kishore', 'Child Plan', false, 'Child endowment plan.', false, false],
            ['103', 'Jeevan Chhaya', 'Child Plan', false, 'Child plan ensuring higher education funds.', false, false],
            ['106', 'Jeevan Surabhi - 25 Yrs', 'Money Back', false, 'Money back plan with increasing life cover (25 Yrs).', false, true],
            ['109', 'Jeevan Vishwas', 'Endowment', false, 'Endowment plan tailored for handicapped dependents.', false, false],
            ['111', 'Bima Kiran', 'Term', false, 'Term protection plan with return of premiums.', false, false],
            ['112', 'Raj Vidyarthi', 'Child Plan', false, 'Classic child education plan.', false, false],
            ['113', 'Jeevan Mangal', 'Rural', false, 'Micro-insurance term plan with return of premium.', false, false],
            ['122', 'Bal Vidya', 'Child Plan', false, 'Single premium child education plan.', true, false],
            ['133', 'Jeevan Mitra (Triple Cover)', 'Endowment', false, 'Endowment plan with triple death benefit.', false, false],
            ['147', 'Jeevan Rekha', 'Whole Life', false, 'Whole life plan with money back features.', false, false],
            ['149', 'Jeevan Anand (Classic)', 'Endowment', false, 'The blockbuster original Jeevan Anand (Table 149).', false, false],
            ['150', 'New Bima Kiran', 'Term', false, 'Upgraded Bima Kiran protection plan.', false, false],
            ['151', 'Jeevan Pramukh', 'Endowment', false, 'High sum assured endowment plan with guaranteed additions.', false, false],
            ['152', 'New Jeevan Akshay I', 'Pension', false, 'Historic immediate annuity plan.', true, false],
            ['153', 'Anmol Jeevan', 'Term', false, 'Classic term assurance plan.', false, false],
            ['162', 'Jeevan Shree-I', 'Endowment', false, 'Endowment plan with Guaranteed Additions.', false, true],
            ['164', 'Anmol Jeevan - 1', 'Term', false, 'Term insurance policy.', false, false],
            ['165', 'Jeevan Tarang (Revised)', 'Whole Life', false, 'Updated version of Jeevan Tarang.', false, false],
            ['168', 'Jeevan Anurag', 'Child Plan', false, 'Child plan funding educational needs at pre-determined intervals.', false, false],
            ['169', 'Jeevan Saral', 'Endowment', false, 'Highly popular flexible premium endowment plan (Table 169).', false, false],
            ['171', 'Sudarshan', 'Endowment', false, 'Historic endowment plan.', false, false],
            ['174', 'Bima Gold', 'Money Back', false, 'The original Bima Gold money back plan.', false, false],
            ['175', 'Bima Bachat', 'Money Back', false, 'Original single premium money back plan.', true, false],
            ['177', 'Jeevan Bharati', 'Endowment', false, 'Exclusive endowment plan for women.', false, false],
            ['178', 'Jeevan Tarang', 'Whole Life', false, 'Whole life plan providing annual survival benefits (Table 178).', false, false],
            ['179', 'New Bima Gold', 'Money Back', false, 'New Bima Gold policy offering survival benefits and extended cover (Table 179).', false, false],
            ['184', 'Child Career Plan', 'Child Plan', false, 'Plan meeting the increasing educational needs of growing children.', false, false],
            ['186', 'Jeevan Amrit', 'Endowment', false, 'Premium return plan.', false, false],
            ['189', 'Jeevan Akshay VI', 'Pension', false, 'Immediate annuity plan (Akshay series VI - Table 189).', true, false],
            ['190', 'Jeevan Ankur', 'Child Plan', false, 'Child plan with income benefit to the family on parent\'s death.', false, false],
            ['193', 'Jeevan Nischay', 'Single Premium', false, 'Highly popular single premium endowment plan.', true, false],
            ['199', 'Jeevan Aastha', 'Single Premium', false, 'Guaranteed single premium endowment plan.', true, false],

            // ════════════════════════════════════════════════════════════════════════
            // 5. OLD ULIP PLANS (Unit Linked)
            // ════════════════════════════════════════════════════════════════════════
            ['172', 'Bima Plus', 'ULIP', false, 'Early Unit Linked Insurance Plan.', false, false],
            ['173', 'Future Plus', 'ULIP', false, 'Unit linked plan.', false, false],
            ['180', 'Money Plus', 'ULIP', false, 'Unit linked money back plan.', false, false],
            ['181', 'Market Plus', 'ULIP', false, 'Unit linked pension plan.', false, false],
            ['187', 'Fortune Plus', 'ULIP', false, 'Unit linked premium plan.', false, false],
            ['188', 'Profit Plus', 'ULIP', false, 'Unit linked endowment plan.', false, false],
            ['191', 'Market Plus I', 'ULIP', false, 'Unit linked pension plan.', false, false],
            ['192', 'Money Plus I', 'ULIP', false, 'Unit linked money back plan.', false, false],
            ['801', 'Endowment Plus', 'ULIP', false, 'Unit linked endowment plan.', false, false],
            ['802', 'Bima Account 1', 'ULIP', false, 'Variable insurance plan.', false, false],
            ['803', 'Bima Account 2', 'ULIP', false, 'Variable insurance plan for higher premiums.', false, false],
            ['835', 'New Endowment Plus', 'ULIP', false, '800-series Unit Linked Endowment.', false, false],
        ];

        $planRepo = $this->entityManager->getRepository(LicPlan::class);
        
        $inserted = 0;
        $updated = 0;
        $skippedCount = 0;

        foreach ($licPlansDataset as $data) {
            [$tableNo, $planName, $categoryName, $isActive, $desc, $isSinglePremium, $isLimitedPremium] = $data;

            $planType = $typeMap[strtoupper($categoryName)] ?? null;

            if (!$planType) {
                $io->warning("Plan Type '{$categoryName}' not found for Table {$tableNo}. Skipping.");
                continue;
            }

            // Check if Plan already exists by Table Number
            $plan = $planRepo->findOneBy(['tableNumber' => $tableNo]);

            if ($plan) {
                // Determine if an update is needed
                $needsUpdate = false;

                if ($plan->getPlanType() !== $planType) {
                    $plan->setPlanType($planType);
                    $needsUpdate = true;
                }
                if ($plan->isActive() !== $isActive) {
                    $plan->setIsActive($isActive);
                    $needsUpdate = true;
                }
                // Always sync the new flags (they default to false, so first run
                // will update every existing row that needs a flag set to true).
                if ($plan->isSinglePremium() !== $isSinglePremium) {
                    $plan->setIsSinglePremium($isSinglePremium);
                    $needsUpdate = true;
                }
                
                if ($plan->isLimitedPremium() !== $isLimitedPremium) {
                    $plan->setIsLimitedPremium($isLimitedPremium);
                    $needsUpdate = true;
                }

                // Only update description if it's currently empty
                if (empty($plan->getDescription())) {
                    $plan->setDescription($desc);
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $updated++;
                    $sp = $isSinglePremium  ? ' [SP]' : '';
                    $lp = $isLimitedPremium ? ' [LP]' : '';
                    $io->text("  <comment>UPDATED</comment> Table {$tableNo} - {$planName}{$sp}{$lp}");
                } else {
                    $skippedCount++;
                }
            } else {
                $plan = new LicPlan();
                $plan->setTableNumber($tableNo);
                $plan->setPlanName($planName);
                $plan->setPlanType($planType);
                $plan->setIsActive($isActive);
                $plan->setDescription($desc);
                $plan->setIsSinglePremium($isSinglePremium);
                $plan->setIsLimitedPremium($isLimitedPremium);

                $this->entityManager->persist($plan);
                $inserted++;
                $sp = $isSinglePremium  ? ' [SP]' : '';
                $lp = $isLimitedPremium ? ' [LP]' : '';
                $io->text("  <info>INSERTED</info> Table {$tableNo} - {$planName}{$sp}{$lp}");
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

        $io->note('[SP] = Single Premium flag set  |  [LP] = Limited Premium flag set');

        return Command::SUCCESS;
    }
}