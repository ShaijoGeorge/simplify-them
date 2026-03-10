<?php

namespace App\Entity;

use App\Repository\PremiumReceiptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PremiumReceiptRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['receiptNumber'], message: 'This Receipt Number already exists.')]
class PremiumReceipt
{
    // Status Constants
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COLLECTED_ONLY = 'COLLECTED_ONLY';
    public const STATUS_PAID_ONLY = 'PAID_ONLY';
    public const STATUS_COMPLETED = 'COMPLETED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $receiptNumber = null;

    // ── Expected Premium ──────────────────────────────────────────────

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $basePremium = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $licFineAmount = '0.00';

    // ── Phase 1: Client → Agent (Collection) ──────────────────────────

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $collectedAmount = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $collectedDate = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $collectionMethod = null;

    // ── Phase 2: Agent → LIC (Payment) ────────────────────────────────

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $paidToLicAmount = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $paidToLicDate = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $paymentChannel = null;

    // ── Status ────────────────────────────────────────────────────────

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    // ── Relationships ─────────────────────────────────────────────────

    #[ORM\ManyToOne(inversedBy: 'premiumReceipts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Policy $policy = null;

    #[ORM\ManyToOne(inversedBy: 'premiumReceipts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agency $agency = null;

    // ── Commission ────────────────────────────────────────────────────

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $grossCommission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $tdsOnCommission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $netCommission = null;

    // ── Audit ─────────────────────────────────────────────────────────

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

    // ── Lifecycle Callbacks ───────────────────────────────────────────

    /**
     * Auto-derive status from which phase fields are populated.
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function deriveStatus(): void
    {
        $hasCollection = $this->collectedAmount !== null && $this->collectedAmount !== '';
        $hasPayment    = $this->paidToLicAmount !== null && $this->paidToLicAmount !== '';

        if ($hasCollection && $hasPayment) {
            $this->status = self::STATUS_COMPLETED;
        } elseif ($hasCollection) {
            $this->status = self::STATUS_COLLECTED_ONLY;
        } elseif ($hasPayment) {
            $this->status = self::STATUS_PAID_ONLY;
        } else {
            $this->status = self::STATUS_PENDING;
        }
    }

    // ── Getters / Setters ─────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReceiptNumber(): ?string
    {
        return $this->receiptNumber;
    }

    public function setReceiptNumber(string $receiptNumber): static
    {
        $this->receiptNumber = $receiptNumber;
        return $this;
    }

    // Base Premium

    public function getBasePremium(): ?string
    {
        return $this->basePremium;
    }

    public function setBasePremium(string $basePremium): static
    {
        $this->basePremium = $basePremium;
        return $this;
    }

    // LIC Fine

    public function getLicFineAmount(): ?string
    {
        return $this->licFineAmount;
    }

    public function setLicFineAmount(?string $licFineAmount): static
    {
        $this->licFineAmount = $licFineAmount;
        return $this;
    }

    // Collected Amount (Phase 1)

    public function getCollectedAmount(): ?string
    {
        return $this->collectedAmount;
    }

    public function setCollectedAmount(?string $collectedAmount): static
    {
        $this->collectedAmount = $collectedAmount;
        return $this;
    }

    // Collected Date

    public function getCollectedDate(): ?\DateTime
    {
        return $this->collectedDate;
    }

    public function setCollectedDate(?\DateTime $collectedDate): static
    {
        $this->collectedDate = $collectedDate;
        return $this;
    }

    // Collection Method

    public function getCollectionMethod(): ?string
    {
        return $this->collectionMethod;
    }

    public function setCollectionMethod(?string $collectionMethod): static
    {
        $this->collectionMethod = $collectionMethod;
        return $this;
    }

    // Paid to LIC Amount (Phase 2)

    public function getPaidToLicAmount(): ?string
    {
        return $this->paidToLicAmount;
    }

    public function setPaidToLicAmount(?string $paidToLicAmount): static
    {
        $this->paidToLicAmount = $paidToLicAmount;
        return $this;
    }

    // Paid to LIC Date

    public function getPaidToLicDate(): ?\DateTime
    {
        return $this->paidToLicDate;
    }

    public function setPaidToLicDate(?\DateTime $paidToLicDate): static
    {
        $this->paidToLicDate = $paidToLicDate;
        return $this;
    }

    // Payment Channel

    public function getPaymentChannel(): ?string
    {
        return $this->paymentChannel;
    }

    public function setPaymentChannel(?string $paymentChannel): static
    {
        $this->paymentChannel = $paymentChannel;
        return $this;
    }

    // Status

    public function getStatus(): string
    {
        return $this->status;
    }

    // No public setter — status is auto-derived via deriveStatus().

    // Policy

    public function getPolicy(): ?Policy
    {
        return $this->policy;
    }

    public function setPolicy(?Policy $policy): static
    {
        $this->policy = $policy;
        return $this;
    }

    // Agency

    public function getAgency(): ?Agency
    {
        return $this->agency;
    }

    public function setAgency(?Agency $agency): static
    {
        $this->agency = $agency;
        return $this;
    }

    // Commission

    public function getGrossCommission(): ?string
    {
        return $this->grossCommission;
    }

    public function setGrossCommission(?string $grossCommission): static
    {
        $this->grossCommission = $grossCommission;
        return $this;
    }

    public function getTdsOnCommission(): ?string
    {
        return $this->tdsOnCommission;
    }

    public function setTdsOnCommission(?string $tdsOnCommission): static
    {
        $this->tdsOnCommission = $tdsOnCommission;
        return $this;
    }

    public function getNetCommission(): ?string
    {
        return $this->netCommission;
    }

    public function setNetCommission(?string $netCommission): static
    {
        $this->netCommission = $netCommission;
        return $this;
    }

    // Audit

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

    // String Representation

    public function __toString(): string
    {
        return $this->receiptNumber ?? 'New Receipt';
    }

    /**
     * Helper: total amount due to LIC (basePremium + fine).
     */
    public function getTotalDueToLic(): float
    {
        return (float) ($this->basePremium ?? 0) + (float) ($this->licFineAmount ?? 0);
    }
}
