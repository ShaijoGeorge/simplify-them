<?php

namespace App\Entity;

use App\Repository\ClientTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ClientTransactionRepository::class)]
class ClientTransaction
{
    // Transaction types
    public const TYPE_COLLECTION  = 'COLLECTION';   // Client → Agency (agent collects from client)
    public const TYPE_PAID_TO_LIC = 'PAID_TO_LIC';  // Agency pays LIC on behalf of client
    public const TYPE_SETTLEMENT  = 'SETTLEMENT';   // Balance settlement (legacy)
    public const TYPE_REFUND      = 'REFUND';       // Agency pays client back (e.g. overpayment)
    public const TYPE_ADJUSTMENT  = 'ADJUSTMENT';   // Manual correction / carry-forward

    public const TYPES = [
        'Collection (Client → Agency)'  => self::TYPE_COLLECTION,
        'Refund to Client (Agency → Client)' => self::TYPE_REFUND,
        'Adjustment (+/-)'              => self::TYPE_ADJUSTMENT,
    ];

    // All types including auto-generated ones (for display)
    public const ALL_TYPES = [
        'Collection (Client → Agency)'  => self::TYPE_COLLECTION,
        'Paid to LIC'                   => self::TYPE_PAID_TO_LIC,
        'Refund to Client'              => self::TYPE_REFUND,
        'Settlement'                    => self::TYPE_SETTLEMENT,
        'Adjustment'                    => self::TYPE_ADJUSTMENT,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'clientTransactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agency $agency = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?PremiumReceipt $premiumReceipt = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $transactionDate = null;

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

    // ──── Getters & Setters ────

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAgency(): ?Agency
    {
        return $this->agency;
    }

    public function setAgency(?Agency $agency): static
    {
        $this->agency = $agency;
        return $this;
    }

    public function getPremiumReceipt(): ?PremiumReceipt
    {
        return $this->premiumReceipt;
    }

    public function setPremiumReceipt(?PremiumReceipt $premiumReceipt): static
    {
        $this->premiumReceipt = $premiumReceipt;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getTransactionDate(): ?\DateTime
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(\DateTime $transactionDate): static
    {
        $this->transactionDate = $transactionDate;
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
     * Returns the signed amount for balance computation.
     *
     * COLLECTION  → client paid agency, so agency's receivable decreases (negative from client perspective)
     * PAID_TO_LIC → agency paid on client's behalf, so client owes agency (positive)
     * SETTLEMENT  → context-dependent: positive = client pays off debt, negative = agency refunds
     * ADJUSTMENT  → context-dependent, stored as-is
     */
    public function getSignedAmount(): float
    {
        $amt = (float) ($this->amount ?? 0);

        return match ($this->type) {
            self::TYPE_PAID_TO_LIC => $amt,       // Money Out (Asset increment / Client debt)
            self::TYPE_REFUND      => $amt,       // Money Out (Asset increment / Debt reduction)
            self::TYPE_COLLECTION  => -$amt,      // Money In  (Liability reduction / Client credit)
            self::TYPE_SETTLEMENT  => -$amt,      // Legacy: Sign reversed by default
            self::TYPE_ADJUSTMENT  => $amt,       // Stored with intended sign already
            default                => 0.0,
        };
    }

    public function __toString(): string
    {
        return sprintf(
            '%s ₹%s (%s)',
            $this->type ?? 'TXN',
            number_format((float) ($this->amount ?? 0), 2),
            $this->transactionDate?->format('d/m/Y') ?? 'N/A'
        );
    }
}
