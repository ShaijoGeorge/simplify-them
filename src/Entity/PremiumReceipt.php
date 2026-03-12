<?php

namespace App\Entity;

use App\Repository\PremiumReceiptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PremiumReceiptRepository::class)]
#[UniqueEntity(fields: ['receiptNumber'], message: 'This Receipt Number already exists.')]
class PremiumReceipt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $receiptNumber = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $paymentDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $paymentMode = null;

    #[ORM\ManyToOne(inversedBy: 'premiumReceipts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Policy $policy = null;

    #[ORM\ManyToOne(inversedBy: 'premiumReceipts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agency $agency = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $grossCommission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $tdsOnCommission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $netCommission = null;

    /** Amount actually collected from the client (may differ from LIC payment amount). */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $collectedFromClient = null;

    /** Date the agent collected money from the client (may differ from LIC payment date). */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $collectionDate = null;

    /** Mode used to collect from client (Cash, UPI/Online, Cheque). */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $collectionMode = null;

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

    public function getPaymentDate(): ?\DateTime
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(?\DateTime $paymentDate): static
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPaymentMode(): ?string
    {
        return $this->paymentMode;
    }

    public function setPaymentMode(?string $paymentMode): static
    {
        $this->paymentMode = $paymentMode;

        return $this;
    }

    public function getPolicy(): ?Policy
    {
        return $this->policy;
    }

    public function setPolicy(?Policy $policy): static
    {
        $this->policy = $policy;

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

    public function __toString(): string
    {
        return $this->receiptNumber ?? 'New Receipt';
    }

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

    public function getCollectedFromClient(): ?string
    {
        return $this->collectedFromClient;
    }

    public function setCollectedFromClient(?string $collectedFromClient): static
    {
        $this->collectedFromClient = $collectedFromClient;
        return $this;
    }

    public function getCollectionDate(): ?\DateTime
    {
        return $this->collectionDate;
    }

    public function setCollectionDate(?\DateTime $collectionDate): static
    {
        $this->collectionDate = $collectionDate;
        return $this;
    }

    public function getCollectionMode(): ?string
    {
        return $this->collectionMode;
    }

    public function setCollectionMode(?string $collectionMode): static
    {
        $this->collectionMode = $collectionMode;
        return $this;
    }
}
