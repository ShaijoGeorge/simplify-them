<?php

namespace App\Entity;

use App\Repository\PolicyRiderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PolicyRider - an optional add-on benefit attached to a base Policy.
 *
 * Rider types:
 *   DAB        - Double Accident Benefit (doubles SA on accidental death)
 *   PWBP       - Premium Waiver Benefit on disability
 *   CI         - Critical Illness Benefit
 *   TERM_RIDER - Additional term assurance rider
 *
 * Business rules enforced elsewhere:
 *   - If a DAB rider is ACTIVE and the policy status = DEATH_CLAIM, a flag
 *     is raised so the agent knows an additional SA amount is claimable.
 *   - Rider premium is included in premium-receipt PDF breakdowns.
 */
#[ORM\Entity(repositoryClass: PolicyRiderRepository::class)]
class PolicyRider
{
    // Rider type constants
    public const TYPE_DAB        = 'DAB';
    public const TYPE_PWBP       = 'PWBP';
    public const TYPE_CI         = 'CI';
    public const TYPE_TERM_RIDER = 'TERM_RIDER';

    public const RIDER_TYPES = [
        self::TYPE_DAB        => 'DAB - Double Accident Benefit',
        self::TYPE_PWBP       => 'PWBP - Premium Waiver on Disability',
        self::TYPE_CI         => 'CI - Critical Illness',
        self::TYPE_TERM_RIDER => 'Term Rider - Additional Term Cover',
    ];

    // Primary Key
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Relationships
    #[ORM\ManyToOne(inversedBy: 'riders')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Policy $policy = null;

    // Core Rider Fields

    /** Rider type code: DAB | PWBP | CI | TERM_RIDER */
    #[ORM\Column(length: 30)]
    private ?string $riderType = null;

    /**
     * Sum assured specific to this rider.
     * e.g. For DAB: equal to base SA (doubles on accidental death).
     * Nullable - PWBP has no separate SA.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $riderSumAssured = null;

    /** Additional annual premium charged for this rider. NOT nullable. */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $riderPremium = null;

    /**
     * Date the rider became effective.
     * Usually same as the base policy's commencementDate.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $riderStartDate = null;

    /**
     * Date the rider expires (nullable).
     * When null the rider runs for the full policy term.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $riderEndDate = null;

    /** Whether this rider is currently in force. */
    #[ORM\Column]
    private bool $isActive = true;

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

    /**
     * Returns true when:
     *   - This is a DAB rider
     *   - The rider is currently active
     *   - The rider has not expired (or has no end date)
     *
     * Used by the death-claim DAB flag in the Policy detail view.
     */
    public function isActiveDabRider(): bool
    {
        if ($this->riderType !== self::TYPE_DAB || !$this->isActive) {
            return false;
        }

        if ($this->riderEndDate !== null) {
            $today = new \DateTime();
            $today->setTime(0, 0, 0);
            return $this->riderEndDate >= $today;
        }

        return true;
    }

    /** Human-readable label for the rider type. Used in Twig and PDFs. */
    public function getRiderTypeLabel(): string
    {
        return self::RIDER_TYPES[$this->riderType] ?? ($this->riderType ?? '-');
    }

    /** Rider premium as float (safe for arithmetic). */
    public function getRiderPremiumFloat(): float
    {
        return (float) ($this->riderPremium ?? '0');
    }

    /** Rider SA as float (0 when not set). */
    public function getRiderSumAssuredFloat(): float
    {
        return (float) ($this->riderSumAssured ?? '0');
    }

    // Getters & Setters

    public function getId(): ?int { return $this->id; }

    public function getPolicy(): ?Policy { return $this->policy; }
    public function setPolicy(?Policy $policy): static { $this->policy = $policy; return $this; }

    public function getRiderType(): ?string { return $this->riderType; }
    public function setRiderType(string $riderType): static { $this->riderType = $riderType; return $this; }

    public function getRiderSumAssured(): ?string { return $this->riderSumAssured; }
    public function setRiderSumAssured(?string $riderSumAssured): static { $this->riderSumAssured = $riderSumAssured; return $this; }

    public function getRiderPremium(): ?string { return $this->riderPremium; }
    public function setRiderPremium(string $riderPremium): static { $this->riderPremium = $riderPremium; return $this; }

    public function getRiderStartDate(): ?\DateTime { return $this->riderStartDate; }
    public function setRiderStartDate(\DateTime $riderStartDate): static { $this->riderStartDate = $riderStartDate; return $this; }

    public function getRiderEndDate(): ?\DateTime { return $this->riderEndDate; }
    public function setRiderEndDate(?\DateTime $riderEndDate): static { $this->riderEndDate = $riderEndDate; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

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
        $label = $this->getRiderTypeLabel();
        if ($this->riderPremium) {
            $label .= ' (₹' . number_format((float) $this->riderPremium, 2) . '/yr)';
        }
        return $label;
    }
}