<?php

namespace App\Entity;

use App\Repository\PolicyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: PolicyRepository::class)]
#[ORM\HasLifecycleCallbacks]
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

    public function __construct()
    {
        $this->premiumReceipts = new ArrayCollection();
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

    /**
     * LOGIC 1: Automatic Date Calculations (Maturity & Next Due)
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

        // Calculate Next Due Date (Only if not manually set)
        // This is useful for NEW policies. For old policies, the agent usually types the date manually.
        if ($this->commencementDate && $this->premiumMode && $this->nextDueDate === null) {
            $nextDue = clone $this->commencementDate;
            $shouldUpdate = true;
            
            switch ($this->premiumMode) {
                case 'YLY':
                    $nextDue->modify('+1 year');
                    break;
                case 'HLY':
                    $nextDue->modify('+6 months');
                    break;
                case 'QLY':
                    $nextDue->modify('+3 months');
                    break;
                case 'NACH':
                case 'MLY':
                    $nextDue->modify('+1 month');
                    break;
                case 'SINGLE':
                    $shouldUpdate = false; // No next due date for Single Premium
                    break;
                default:
                    $shouldUpdate = false;
            }
            if ($shouldUpdate) {
                $this->setNextDueDate($nextDue);
            }
        }
    }

    /**
     * LOGIC 2: Automatic Financials (GST & Total Premium)
     * Handles the Sept 2025 Tax Reform Logic
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateTotals(): void
    {
        // Define the GST Reform Date (22 Sept 2025)
        $gstReformDate = new \DateTime('2025-09-22');

        // Determine GST Rate based on DOC (Old Regime vs New Regime)
        $gstRate = 0.0;

        if ($this->commencementDate && $this->commencementDate < $gstReformDate) {
            // --- OLD TAX REGIME (Before Sep 2025) ---
            $planTypeName = '';
            if ($this->licPlan && $this->licPlan->getPlanType()) {
                $planTypeName = $this->licPlan->getPlanType()->getName();
            }

            if (str_contains(strtoupper($planTypeName), 'TERM')) {
                $gstRate = 18.0; // Old Term Plan Rate
            } else {
                // Endowment/Traditional: 4.5% 1st Year is standard
                $gstRate = 4.5; 
            }
            
        } else {
            // --- NEW TAX REGIME (After Sep 2025) ---
            // Individual Life Insurance is now 0% GST
            $gstRate = 0.0;
        }

        // Logic: If Basic is set, Calculate GST and Total
        if ($this->basicPremium) {
            
            // Calculate GST amount
            $calculatedGst = ($this->basicPremium * $gstRate) / 100;
            
            // Set GST (We cast to string for Decimal type compatibility)
            $this->setGst((string)$calculatedGst);

            // Set Total Premium
            $total = $this->basicPremium + $calculatedGst;
            $this->setTotalPremium((string)$total);
        }
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

    public function __toString(): string
    {
        return (string) $this->policyNumber;
    }
}
