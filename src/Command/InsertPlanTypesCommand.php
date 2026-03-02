<?php

namespace App\Command;

use App\Entity\LicPlanType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Seeds / reconciles the lic_plan_type table.
 *
 * Safe to re-run at any time:
 *   - Existing rows whose name matches a canonical name  → SKIPPED (description silently refreshed).
 *   - Existing rows whose name matches a known alias     → RENAMED in-place
 *     (preserves row ID so all linked LicPlan FKs stay intact).
 *   - Missing rows                                       → INSERTED.
 *
 * ⚠  GST-sensitive naming rules enforced by Policy::calculateTotals() and policy_dates.js:
 *   • Name contains "Term" (case-insensitive) → 18 % flat (old regime)
 *   • premium_mode = SINGLE at policy level   → 1.25 % (old regime)
 *   • Everything else                         → 4.5 % yr-1 / 2.25 % yr-2+ (old regime)
 *   • DOC ≥ 22 Sep 2025                       → 0 % regardless of plan type
 *
 * Run with:
 *   php bin/console app:insert-plan-types
 */
#[AsCommand(
    name: 'app:insert-plan-types',
    description: 'Seeds and reconciles all LIC plan-type categories (safe to re-run).',
)]
class InsertPlanTypesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Seeding / Reconciling LIC Plan Types');

        /**
         * Each entry:
         *   'name'        → canonical name stored in DB going forward.
         *                   The GST engine reads this — do NOT rename without
         *                   updating Policy::calculateTotals() and policy_dates.js.
         *   'aliases'     → legacy names already in DB to be renamed to 'name'.
         *                   Rename is in-place (same row ID) so linked LicPlan
         *                   records remain valid.
         *   'description' → human-readable text shown in EasyAdmin.
         */
        $planTypes = [
            [
                'name'        => 'Endowment',
                'aliases'     => [],
                'description' =>
                    'Classic savings-cum-protection plan. Sum Assured paid on maturity or death, '
                    . 'whichever is earlier. Examples: New Endowment Plan (Table 714), '
                    . 'New Jeevan Anand (Table 715), Jeevan Labh (Table 736). '
                    . 'GST: 4.5% Year 1 → 2.25% Year 2+ (old regime); 0% post Sep-2025.',
            ],
            [
                'name'        => 'Money Back',
                'aliases'     => [],
                'description' =>
                    'Periodic survival benefits paid during the policy term plus maturity '
                    . 'and death cover. Examples: New Money Back – 20yr (Table 720), '
                    . 'New Money Back – 25yr (Table 721), Bima Shree (Table 748). '
                    . 'GST: 4.5% Year 1 → 2.25% Year 2+ (old regime); 0% post Sep-2025.',
            ],
            [
                'name'        => 'Whole Life',
                'aliases'     => [],
                'description' =>
                    'Lifelong protection; Sum Assured paid on death at any age. '
                    . 'Examples: Jeevan Umang (Table 745), Jeevan Utsav (Table 771). '
                    . 'GST: 4.5% Year 1 → 2.25% Year 2+ (old regime); 0% post Sep-2025.',
            ],
            [
                // ⚠  The word "Term" MUST remain in this name.
                //    Policy::calculateTotals() and policy_dates.js use
                //    str_contains(strtoupper($name), 'TERM') to route to the 18% bracket.
                'name'    => 'Term',
                'aliases' => ['Term Assurance', 'Term Insurance', 'Term Plan', 'Credit Life'],
                'description' =>
                    'Pure risk / protection plan — death benefit only, no maturity value. '
                    . 'Examples: New Jeevan Amar (Table 955), New Tech-Term (Table 954), '
                    . 'Saral Jeevan Bima (Table 859). '
                    . 'GST: flat 18% all years (old regime); 0% post Sep-2025. '
                    . '⚠ The word "Term" in this name is required by the GST calculation engine.',
            ],
            [
                'name'        => 'Single Premium',
                'aliases'     => ['Single Premium Endowment'],
                'description' =>
                    'One-time lump-sum premium plan; full premium paid upfront. '
                    . 'Examples: Single Premium Endowment (Table 717), Jeevan Utsav SP (Table 883). '
                    . 'GST is driven by premium_mode = SINGLE at policy level: '
                    . '1.25% one-time (old regime); 0% post Sep-2025.',
            ],
            [
                'name'        => 'Pension',
                'aliases'     => ['Annuity', 'Pension / Annuity'],
                'description' =>
                    'Retirement-focused deferred or immediate annuity plans. '
                    . 'Examples: Jeevan Dhara-II (Table 872), New Pension Plus (Table 867), '
                    . 'Smart Pension (Table 879). '
                    . 'GST: Exempt / special rates (old regime); 0% post Sep-2025.',
            ],
            [
                'name'        => 'ULIP',
                'aliases'     => ['Unit Linked', 'Unit Linked Insurance Plan'],
                'description' =>
                    'Market-linked investment combined with life cover. '
                    . 'Examples: SIIP (Table 752), Nivesh Plus (Table 749), Index Plus (Table 873). '
                    . 'GST: 18% on charges (fund management, policy administration) all years '
                    . '(old regime); 0% post Sep-2025.',
            ],
            [
                'name'        => 'Health',
                'aliases'     => ['Health Insurance', 'Critical Illness'],
                'description' =>
                    'Health insurance and critical illness plans. '
                    . 'Examples: Jeevan Arogya (Table 904), Cancer Cover (Table 905). '
                    . 'GST: 18% (old regime); 0% post Sep-2025 for life-linked products.',
            ],
            [
                'name'        => 'Child Plan',
                'aliases'     => ['Children Plan', "Children's Plan"],
                'description' =>
                    "Savings and protection plan targeting a child's future milestones "
                    . '(education, marriage). '
                    . "Examples: Amritbaal (Table 774), New Children's Money Back (Table 732), "
                    . 'Jeevan Lakshya (Table 733). '
                    . 'GST: 4.5% Year 1 → 2.25% Year 2+ (old regime); 0% post Sep-2025.',
            ],
            [
                'name'        => 'Micro Insurance',
                'aliases'     => ['Micro', 'Microinsurance'],
                'description' =>
                    'Low-premium, small Sum Assured plans for low-income segments. '
                    . 'Examples: Bhagya Lakshmi (Table 939), Jeevan Mangal (Table 940). '
                    . 'GST: standard traditional rates apply where applicable.',
            ],
            [
                'name'        => 'Group Insurance',
                'aliases'     => ['Group', 'Group Term'],
                'description' =>
                    'Employer-employee or affinity-group life cover schemes. '
                    . 'Examples: Group Term Life, PMSBY, EDLI. '
                    . 'GST and premium structure governed by group scheme rules.',
            ],
            [
                'name'        => 'Rural',
                'aliases'     => ['Rural Plan'],
                'description' =>
                    'Plans for rural and semi-urban populations per IRDAI rural obligations. '
                    . 'Examples: Jeevan Mangal (Table 113), Bima Kiran (Table 860). '
                    . 'GST: standard traditional rates apply where applicable.',
            ],
            [
                'name'        => 'Rider',
                'aliases'     => ['Add-on', 'Optional Benefit'],
                'description' =>
                    'Optional add-on coverage attached to a base policy. '
                    . 'Examples: Accidental Death & Disability Benefit (AD&DB), Term Assurance Rider, Critical Illness Rider. '
                    . 'GST: Often taxed at 18% independently of the base plan (old regime); 0% post Sep-2025.',
            ],
        ];

        $repo     = $this->entityManager->getRepository(LicPlanType::class);
        $inserted = 0;
        $renamed  = 0;
        $skipped  = 0;

        foreach ($planTypes as $data) {
            $canonicalName = $data['name'];
            $aliases       = $data['aliases'] ?? [];
            $description   = $data['description'];

            // ── 1. Canonical name already exists → skip (refresh description) ─
            $existing = $repo->findOneBy(['name' => $canonicalName]);
            if ($existing) {
                $existing->setDescription($description);
                $io->text(sprintf(
                    '  <comment>SKIP  </comment>  %s',
                    $canonicalName
                ));
                $skipped++;
                continue;
            }

            // ── 2. Found under a legacy alias → rename in-place ───────────────
            $legacyRow = null;
            $matchedAlias = '';
            foreach ($aliases as $alias) {
                $legacyRow = $repo->findOneBy(['name' => $alias]);
                if ($legacyRow) {
                    $matchedAlias = $alias;
                    break;
                }
            }

            if ($legacyRow) {
                $legacyRow->setName($canonicalName);
                $legacyRow->setDescription($description);
                $io->text(sprintf(
                    '  <info>RENAME</info>  "%s"  →  "%s"  (ID %d kept — linked LIC Plans unaffected)',
                    $matchedAlias,
                    $canonicalName,
                    $legacyRow->getId()
                ));
                $renamed++;
                continue;
            }

            // ── 3. Not found anywhere → insert new row ────────────────────────
            $planType = new LicPlanType();
            $planType->setName($canonicalName);
            $planType->setDescription($description);
            $this->entityManager->persist($planType);
            $io->text(sprintf('  <info>INSERT</info>  %s', $canonicalName));
            $inserted++;
        }

        $this->entityManager->flush();

        $io->newLine();
        $io->success(sprintf(
            'Done.  Inserted: %d  |  Renamed: %d  |  Skipped (already correct): %d',
            $inserted,
            $renamed,
            $skipped
        ));

        if ($renamed > 0) {
            $io->note(
                'Renamed rows kept their original IDs. '
                . 'All LIC Plans linked to those types continue to work without any changes.'
            );
        }

        return Command::SUCCESS;
    }
}