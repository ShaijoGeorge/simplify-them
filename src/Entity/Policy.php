<?php

namespace App\Entity;

use App\Repository\PolicyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PolicyRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['policyNumber'], message: 'This Policy Number already exists in the system.')]
class Policy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $policyNumber = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $commencementDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $sumAssured = null;

    #[ORM\Column]
    private ?int $policyTerm = null;

    #[ORM\Column]
    private ?int $premiumPayingTerm = null;

    #[ORM\Column(length: 20)]
    private ?string $premiumMode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $basicPremium = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $gst = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPremium = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $nextDueDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $fup = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $maturityDate = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?LicPlan $licPlan = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agency $agency = null;

    /**
     * @var Collection<int, PremiumReceipt>
     */
    #[ORM\OneToMany(targetEntity: PremiumReceipt::class, mappedBy: 'policy')]
    private Collection $premiumReceipts;

    /**
     * @var Collection<int, Nominee>
     */
    #[ORM\OneToMany(targetEntity: Nominee::class, mappedBy: 'policy', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $nominees;

    #[ORM\OneToMany(targetEntity: PolicyRider::class, mappedBy: 'policy', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $riders;

    #[ORM\OneToMany(targetEntity: SurvivalBenefit::class, mappedBy: 'policy', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $survivalBenefits;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Gedmo\Blameable(on: 'create')]
    private ?string $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Gedmo\Blameable(on: 'update')]
    private ?string $updatedBy = null;

    #[ORM\Column(nullable: true)]
    private ?int $entryAge = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lifeAssuredName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $lifeAssuredDob = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $lifeAssuredGender = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $licBondNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $licBranch = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $paidUpSumAssured = null;

    // The tabular annual premium from LIC's plan table.
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $annualPremium = null;

    // The modal rebate factor applied (e.g. 0.51 for HLY, 0.26 for QLY).
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 4, nullable: true)]
    private ?string $modalRebate = null;

    // Stores the policy year (1, 2, 3 …) that was in effect when GST was last calculated.
    // Used for auditing and display purposes.
    #[ORM\Column(nullable: true)]
    private ?int $gstPolicyYear = null;

    public function __construct()
    {
        $this->premiumReceipts = new ArrayCollection();
        $this->nominees = new ArrayCollection();
        $this->riders = new ArrayCollection();
        $this->survivalBenefits = new ArrayCollection();
    }

    // HELPERS

    /**
     * Returns true when either:
     *  (a) the premium mode is explicitly SINGLE, or
     *  (b) the linked LIC plan is flagged as a single-premium product.
     *
     * Used by calculateTotals(), calculateDates(), and the commission engine.
     */
    public function isSinglePremiumPolicy(): bool
    {
        if ($this->premiumMode === 'SINGLE') {
            return true;
        }

        return $this->licPlan?->isSinglePremium() ?? false;
    }

    // LIFECYCLE CALLBACKS

    /**
     * Modal Rebate: auto-calculate basicPremium from annualPremium × factor.
     *
     * Factors: YLY/SINGLE → 1.0, HLY → 0.51, QLY → 0.26, MLY/NACH → 0.0875
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateModalPremium(): void
    {
        if (!$this->annualPremium || !$this->premiumMode) {
            return;
        }

        $factor = match ($this->premiumMode) {
            'HLY', 'HALF-YEARLY'     => 0.51,
            'QLY', 'QUARTERLY'       => 0.26,
            'NACH', 'MLY', 'MONTHLY' => 0.0875,
            default                  => 1.0,   // YLY, SINGLE, etc.
        };

        $this->modalRebate  = (string) $factor;
        $this->basicPremium = (string) round((float) $this->annualPremium * $factor, 2);
    }

    // Calculate entry age from Life Assured DOB vs DOC.
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateEntryAge(): void
    {
        if ($this->lifeAssuredDob && $this->commencementDate) {
            $this->entryAge = $this->lifeAssuredDob->diff($this->commencementDate)->y;
        }
    }

    /**
     * LOGIC 1: Automatic Date Calculations (Maturity & Next Due)
     *
     * Single-premium policies never have a next due date - cleared here
     * regardless of whether the mode was set via premiumMode = SINGLE or
     * via the LicPlan.isSinglePremium flag.
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateDates(): void
    {
        // Calculate Maturity Date (DOC + Term)
        if ($this->commencementDate && $this->policyTerm) {
            $maturity = clone $this->commencementDate;
            $maturity->modify('+' . $this->policyTerm . ' years');
            $this->setMaturityDate($maturity);
        }

        // Single-premium policies have no renewal premiums → no next due date.
        if ($this->isSinglePremiumPolicy()) {
            $this->nextDueDate = null;
            return;
        }

        // Calculate Next Due Date (Only if not manually set)
        if ($this->commencementDate && $this->premiumMode && $this->nextDueDate === null) {
            $nextDue = clone $this->commencementDate;
            $today = new \DateTime();
            $today->setTime(0, 0, 0);

            $interval = match ($this->premiumMode) {
                'YLY', 'YEARLY'         => '+1 year',
                'HLY', 'HALF-YEARLY'    => '+6 months',
                'QLY', 'QUARTERLY'      => '+3 months',
                'NACH', 'MLY', 'MONTHLY'=> '+1 month',
                default                 => null,
            };

            if ($interval) {
                $nextDue->modify($interval);

                while ($nextDue < $today) {
                    $nextDue->modify($interval);
                }

                $this->setNextDueDate($nextDue);
            }
        }
    }

    /**
     * LOGIC 2: Year-aware GST & Total Premium calculation.
     *
     * GST Rate Matrix:
     *
     *  Regime       │ Plan / Mode            │ Year 1 │ Year 2+
     * ──────────────┼────────────────────────┼────────┼────────
     *  Old regime   │ Single Premium *        │  1.25 %│   N/A
     *  (DOC before  │ Term plan               │ 18.00 %│ 18.00 %
     *  22 Sep 2025) │ Traditional             │  4.50 %│  2.25 %
     * ──────────────┼────────────────────────┼────────┼────────
     *  New regime   │ All                     │  0.00 %│  0.00 %
     *  (DOC on/after│                        │        │
     *  22 Sep 2025) │                        │        │
     *
     * * Single Premium = premiumMode === 'SINGLE'  OR  licPlan.isSinglePremium === true
     * * Term          = planType name contains 'TERM' (case-insensitive)
     * * Traditional   = everything else (Endowment, Money Back, Whole Life, etc.)
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateTotals(): void
    {
        if (!$this->basicPremium) {
            return;
        }

        $gstReformDate = new \DateTime('2025-09-22');
        $today         = new \DateTime();

        // Determine current policy year (1-based)
        $policyYear = 1;
        if ($this->commencementDate) {
            $fullYearsElapsed = (int) $this->commencementDate->diff($today)->y;
            $policyYear       = $fullYearsElapsed + 1;
        }

        // Plan category flags
        $planTypeName = '';
        if ($this->licPlan?->getPlanType()) {
            $planTypeName = strtoupper($this->licPlan->getPlanType()->getName());
        }

        $isTerm   = str_contains($planTypeName, 'TERM');

        // Single-premium: check BOTH premiumMode AND LicPlan flag so the GST
        // is always correct even if the agent chose the wrong mode.
        $isSingle = $this->isSinglePremiumPolicy();

        // Resolve GST rate
        $gstRate = 0.0;

        if ($this->commencementDate && $this->commencementDate < $gstReformDate) {
            // Old regime (DOC before 22 Sep 2025)
            if ($isSingle) {
                // Single-premium: 1.25 % one-time (no renewal, so year is irrelevant)
                $gstRate = 1.25;
            } elseif ($isTerm) {
                // Term plans: flat 18 % all years
                $gstRate = 18.0;
            } else {
                // Traditional plans (Endowment, Money Back, Whole Life …)
                $gstRate = ($policyYear === 1) ? 4.5 : 2.25;
            }
        } else {
            // New regime (DOC on/after 22 Sep 2025): always 0 %
            $gstRate = 0.0;
        }

        // Persist the policy year used for this calculation
        $this->gstPolicyYear = $policyYear;

        // Compute and store GST & total
        $calculatedGst = (float) $this->basicPremium * $gstRate / 100;
        $this->setGst((string) $calculatedGst);
        $this->setTotalPremium((string) ((float) $this->basicPremium + $calculatedGst));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPolicyNumber(): ?string
    {
        return $this->policyNumber;
    }

    public function setPolicyNumber(string $policyNumber): static
    {
        $this->policyNumber = $policyNumber;

        return $this;
    }

    public function getCommencementDate(): ?\DateTime
    {
        return $this->commencementDate;
    }

    public function setCommencementDate(\DateTime $commencementDate): static
    {
        $this->commencementDate = $commencementDate;

        return $this;
    }

    public function getSumAssured(): ?string
    {
        return $this->sumAssured;
    }

    public function setSumAssured(string $sumAssured): static
    {
        $this->sumAssured = $sumAssured;

        return $this;
    }

    public function getPolicyTerm(): ?int
    {
        return $this->policyTerm;
    }

    public function setPolicyTerm(int $policyTerm): static
    {
        $this->policyTerm = $policyTerm;

        return $this;
    }

    public function getPremiumPayingTerm(): ?int
    {
        return $this->premiumPayingTerm;
    }

    public function setPremiumPayingTerm(int $premiumPayingTerm): static
    {
        $this->premiumPayingTerm = $premiumPayingTerm;

        return $this;
    }

    public function getPremiumMode(): ?string
    {
        return $this->premiumMode;
    }

    public function setPremiumMode(string $premiumMode): static
    {
        $this->premiumMode = $premiumMode;

        return $this;
    }

    public function getBasicPremium(): ?string
    {
        return $this->basicPremium;
    }

    public function setBasicPremium(string $basicPremium): static
    {
        $this->basicPremium = $basicPremium;

        return $this;
    }

    public function getGst(): ?string
    {
        return $this->gst;
    }

    public function setGst(string $gst): static
    {
        $this->gst = $gst;

        return $this;
    }

    public function getFup(): ?\DateTime
    {
        return $this->fup;
    }

    public function setFup(?\DateTime $fup): static
    {
        $this->fup = $fup;

        return $this;
    }

    public function getTotalPremium(): ?string
    {
        return $this->totalPremium;
    }

    public function setTotalPremium(string $totalPremium): static
    {
        $this->totalPremium = $totalPremium;

        return $this;
    }

    public function getNextDueDate(): ?\DateTime
    {
        return $this->nextDueDate;
    }

    public function setNextDueDate(?\DateTime $nextDueDate): static
    {
        $this->nextDueDate = $nextDueDate;

        return $this;
    }

    public function getMaturityDate(): ?\DateTime
    {
        return $this->maturityDate;
    }

    public function setMaturityDate(?\DateTime $maturityDate): static
    {
        $this->maturityDate = $maturityDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getLicPlan(): ?LicPlan
    {
        return $this->licPlan;
    }

    public function setLicPlan(?LicPlan $licPlan): static
    {
        $this->licPlan = $licPlan;

        return $this;
    }

    public function getAgency(): ?Agency
    {
        return $this->agency;
    }

    public function setAgency(?Agency $agency): static
    {
        $this->agency = $agency;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?string $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?string $updatedBy): static
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function getEntryAge(): ?int
    {
        return $this->entryAge;
    }

    public function setEntryAge(?int $entryAge): static
    {
        $this->entryAge = $entryAge;
        return $this;
    }

    public function getLifeAssuredName(): ?string
    {
        return $this->lifeAssuredName;
    }

    public function setLifeAssuredName(?string $lifeAssuredName): static
    {
        $this->lifeAssuredName = $lifeAssuredName;
        return $this;
    }

    public function getLifeAssuredDob(): ?\DateTime
    {
        return $this->lifeAssuredDob;
    }

    public function setLifeAssuredDob(?\DateTime $lifeAssuredDob): static
    {
        $this->lifeAssuredDob = $lifeAssuredDob;
        return $this;
    }

    public function getLifeAssuredGender(): ?string
    {
        return $this->lifeAssuredGender;
    }

    public function setLifeAssuredGender(?string $lifeAssuredGender): static
    {
        $this->lifeAssuredGender = $lifeAssuredGender;
        return $this;
    }

    public function getLicBondNumber(): ?string
    {
        return $this->licBondNumber;
    }

    public function setLicBondNumber(?string $licBondNumber): static
    {
        $this->licBondNumber = $licBondNumber;
        return $this;
    }

    public function getLicBranch(): ?string
    {
        return $this->licBranch;
    }

    public function setLicBranch(?string $licBranch): static
    {
        $this->licBranch = $licBranch;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getPaidUpSumAssured(): ?string
    {
        return $this->paidUpSumAssured;
    }

    public function setPaidUpSumAssured(?string $paidUpSumAssured): static
    {
        $this->paidUpSumAssured = $paidUpSumAssured;
        return $this;
    }

    public function getAnnualPremium(): ?string
    {
        return $this->annualPremium;
    }

    public function setAnnualPremium(?string $annualPremium): static
    {
        $this->annualPremium = $annualPremium;
        return $this;
    }

    public function getModalRebate(): ?string
    {
        return $this->modalRebate;
    }

    public function setModalRebate(?string $modalRebate): static
    {
        $this->modalRebate = $modalRebate;
        return $this;
    }

    public function getGstPolicyYear(): ?int
    {
        return $this->gstPolicyYear;
    }

    public function setGstPolicyYear(?int $gstPolicyYear): static
    {
        $this->gstPolicyYear = $gstPolicyYear;
        return $this;
    }

    /**
     * @return Collection<int, PremiumReceipt>
     */
    public function getPremiumReceipts(): Collection
    {
        return $this->premiumReceipts;
    }

    public function addPremiumReceipt(PremiumReceipt $premiumReceipt): static
    {
        if (!$this->premiumReceipts->contains($premiumReceipt)) {
            $this->premiumReceipts->add($premiumReceipt);
            $premiumReceipt->setPolicy($this);
        }

        return $this;
    }

    public function removePremiumReceipt(PremiumReceipt $premiumReceipt): static
    {
        if ($this->premiumReceipts->removeElement($premiumReceipt)) {
            // set the owning side to null (unless already changed)
            if ($premiumReceipt->getPolicy() === $this) {
                $premiumReceipt->setPolicy(null);
            }
        }

        return $this;
    }

    // return Collection<int, Nominee>
    public function getNominees(): Collection
    {
        return $this->nominees;
    }

    public function addNominee(Nominee $nominee): static
    {
        if (!$this->nominees->contains($nominee)) {
            $this->nominees->add($nominee);
            $nominee->setPolicy($this);
        }

        return $this;
    }

    public function removeNominee(Nominee $nominee): static
    {
        if ($this->nominees->removeElement($nominee)) {
            if ($nominee->getPolicy() === $this) {
                $nominee->setPolicy(null);
            }
        }

        return $this;
    }

    // Returns total share percentage allocated to all nominees.
    public function getTotalNomineeShare(): float
    {
        $total = 0.0;
        foreach ($this->nominees as $nominee) {
            $total += (float) $nominee->getSharePercentage();
        }
        return $total;
    }

    // Calculates and stores the Paid-Up Sum Assured.
    // Formula: (PPT years elapsed / PPT) * Sum Assured
    // "PPT years elapsed" = full years of premiums paid from DOC up to today
    // (capped at PPT so it never exceeds the full SA).
    // Call this whenever status transitions to PAID_UP.
    public function calculatePaidUpSumAssured(): void
    {
        if (!$this->commencementDate || !$this->premiumPayingTerm || !$this->sumAssured) {
            return;
        }

        $ppt = (int) $this->premiumPayingTerm;
        if ($ppt <= 0) {
            return;
        }

        // Years elapsed since commencement (full years only)
        $elapsed = (int) $this->commencementDate->diff(new \DateTime())->y;

        // Cap at PPT - can never exceed the full SA
        $elapsed = min($elapsed, $ppt);

        $sa = (float) $this->sumAssured;
        $paidUpSA = ($elapsed / $ppt) * $sa;

        $this->paidUpSumAssured = (string) round($paidUpSA, 2);
    }

    public function getRiders(): Collection
    {
        return $this->riders;
    }

    public function addRider(PolicyRider $rider): static
    {
        if (!$this->riders->contains($rider)) {
            $this->riders->add($rider);
            $rider->setPolicy($this);
        }
        return $this;
    }

    public function removeRider(PolicyRider $rider): static
    {
        if ($this->riders->removeElement($rider)) {
            if ($rider->getPolicy() === $this) {
                $rider->setPolicy(null);
            }
        }
        return $this;
    }

    /**
     * Returns the total annual rider premium across all active riders.
     * Used in the receipt PDF premium breakdown.
     */
    public function getTotalRiderPremium(): float
    {
        $total = 0.0;
        foreach ($this->riders as $rider) {
            if ($rider->isActive()) {
                $total += $rider->getRiderPremiumFloat();
            }
        }
        return $total;
    }

    /**
     * Returns true when the policy has at least one active DAB rider.
     * Called from the Policy detail template and death-claim logic.
     */
    public function hasActiveDabRider(): bool
    {
        foreach ($this->riders as $rider) {
            if ($rider->isActiveDabRider()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the total claimable DAB sum assured (sum of all active DAB rider SAs).
     * If a DAB rider has no SA set, falls back to the base sumAssured.
     */
    public function getDabClaimableAmount(): float
    {
        $total = 0.0;
        foreach ($this->riders as $rider) {
            if ($rider->isActiveDabRider()) {
                $riderSA = $rider->getRiderSumAssuredFloat();
                // If rider SA is 0 / not set, use the base policy SA
                $total += ($riderSA > 0) ? $riderSA : (float) $this->sumAssured;
            }
        }
        return $total;
    }

    // Survival Benefits collection
    public function getSurvivalBenefits(): Collection
    {
        return $this->survivalBenefits;
    }

    public function addSurvivalBenefit(SurvivalBenefit $survivalBenefit): static
    {
        if (!$this->survivalBenefits->contains($survivalBenefit)) {
            $this->survivalBenefits->add($survivalBenefit);
            $survivalBenefit->setPolicy($this);
        }
        return $this;
    }

    public function removeSurvivalBenefit(SurvivalBenefit $survivalBenefit): static
    {
        if ($this->survivalBenefits->removeElement($survivalBenefit)) {
            if ($survivalBenefit->getPolicy() === $this) {
                $survivalBenefit->setPolicy(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->policyNumber;
    }
}
