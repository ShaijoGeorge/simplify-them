<?php

namespace App\Entity;

use App\Repository\ClaimRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ClaimRepository::class)]
class Claim
{
    // Claim Type constants
    public const TYPE_DEATH            = 'DEATH';
    public const TYPE_MATURITY         = 'MATURITY';
    public const TYPE_SURVIVAL_BENEFIT = 'SURVIVAL_BENEFIT';
    public const TYPE_ACCIDENTAL_DEATH = 'ACCIDENTAL_DEATH';

    public const CLAIM_TYPES = [
        self::TYPE_DEATH            => 'Death',
        self::TYPE_MATURITY         => 'Maturity',
        self::TYPE_SURVIVAL_BENEFIT => 'Survival Benefit',
        self::TYPE_ACCIDENTAL_DEATH => 'Accidental Death',
    ];

    // Status constants
    public const STATUS_INTIMATED           = 'INTIMATED';
    public const STATUS_DOCUMENTS_SUBMITTED = 'DOCUMENTS_SUBMITTED';
    public const STATUS_UNDER_PROCESS       = 'UNDER_PROCESS';
    public const STATUS_SETTLED             = 'SETTLED';
    public const STATUS_REPUDIATED          = 'REPUDIATED';

    public const STATUSES = [
        self::STATUS_INTIMATED           => 'Intimated',
        self::STATUS_DOCUMENTS_SUBMITTED => 'Documents Submitted',
        self::STATUS_UNDER_PROCESS       => 'Under Process',
        self::STATUS_SETTLED             => 'Settled',
        self::STATUS_REPUDIATED          => 'Repudiated',
    ];

    // Primary Key
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Relationships
    #[ORM\ManyToOne(inversedBy: 'claims')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Policy $policy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agency $agency = null;

    // Core Fields
    #[ORM\Column(length: 30)]
    private ?string $claimType = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $claimDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $claimedAmount = null;

    #[ORM\Column(length: 30)]
    private ?string $status = self::STATUS_INTIMATED;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $settledAmount = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $settlementDate = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $claimantName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    // Audit Fields (Gedmo)
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

    // Business Logic Helpers

    /** Human-readable claim type label. */
    public function getClaimTypeLabel(): string
    {
        return self::CLAIM_TYPES[$this->claimType] ?? ($this->claimType ?? '-');
    }

    /** Human-readable status label. */
    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ($this->status ?? '-');
    }

    /** Claimed amount as float (safe for arithmetic). */
    public function getClaimedAmountFloat(): float
    {
        return (float) ($this->claimedAmount ?? '0');
    }

    /** Settled amount as float (safe for arithmetic). */
    public function getSettledAmountFloat(): float
    {
        return (float) ($this->settledAmount ?? '0');
    }

    // Getters & Setters

    public function getId(): ?int { return $this->id; }

    public function getPolicy(): ?Policy { return $this->policy; }
    public function setPolicy(?Policy $policy): static { $this->policy = $policy; return $this; }

    public function getAgency(): ?Agency { return $this->agency; }
    public function setAgency(?Agency $agency): static { $this->agency = $agency; return $this; }

    public function getClaimType(): ?string { return $this->claimType; }
    public function setClaimType(string $claimType): static { $this->claimType = $claimType; return $this; }

    public function getClaimDate(): ?\DateTime { return $this->claimDate; }
    public function setClaimDate(\DateTime $claimDate): static { $this->claimDate = $claimDate; return $this; }

    public function getClaimedAmount(): ?string { return $this->claimedAmount; }
    public function setClaimedAmount(string $claimedAmount): static { $this->claimedAmount = $claimedAmount; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getSettledAmount(): ?string { return $this->settledAmount; }
    public function setSettledAmount(?string $settledAmount): static { $this->settledAmount = $settledAmount; return $this; }

    public function getSettlementDate(): ?\DateTime { return $this->settlementDate; }
    public function setSettlementDate(?\DateTime $settlementDate): static { $this->settlementDate = $settlementDate; return $this; }

    public function getClaimantName(): ?string { return $this->claimantName; }
    public function setClaimantName(?string $claimantName): static { $this->claimantName = $claimantName; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getCreatedBy(): ?string { return $this->createdBy; }
    public function setCreatedBy(?string $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getUpdatedBy(): ?string { return $this->updatedBy; }
    public function setUpdatedBy(?string $updatedBy): static { $this->updatedBy = $updatedBy; return $this; }

    public function __toString(): string
    {
        $label = $this->getClaimTypeLabel() . ' Claim';
        if ($this->claimDate) {
            $label .= ' - ' . $this->claimDate->format('d-M-Y');
        }
        if ($this->claimedAmount) {
            $label .= ' (₹' . number_format((float) $this->claimedAmount, 2) . ')';
        }
        return $label;
    }
}
