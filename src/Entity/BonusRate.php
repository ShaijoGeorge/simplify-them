<?php

namespace App\Entity;

use App\Repository\BonusRateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: BonusRateRepository::class)]
#[ORM\UniqueConstraint(name: 'uq_bonus_rate_plan_year', columns: ['lic_plan_id', 'financial_year'])]
#[UniqueEntity(fields: ['licPlan', 'financialYear'], message: 'A bonus rate for this plan and financial year already exists.')]
class BonusRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?LicPlan $licPlan = null;

    #[ORM\Column(length: 10)]
    private ?string $financialYear = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private ?string $simpleReversionaryBonus = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $finalAdditionalBonus = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $loyaltyAddition = null;

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

    public function getLicPlan(): ?LicPlan
    {
        return $this->licPlan;
    }

    public function setLicPlan(?LicPlan $licPlan): static
    {
        $this->licPlan = $licPlan;

        return $this;
    }

    public function getFinancialYear(): ?string
    {
        return $this->financialYear;
    }

    public function setFinancialYear(string $financialYear): static
    {
        $this->financialYear = $financialYear;

        return $this;
    }

    public function getSimpleReversionaryBonus(): ?string
    {
        return $this->simpleReversionaryBonus;
    }

    public function setSimpleReversionaryBonus(string $simpleReversionaryBonus): static
    {
        $this->simpleReversionaryBonus = $simpleReversionaryBonus;

        return $this;
    }

    public function getFinalAdditionalBonus(): ?string
    {
        return $this->finalAdditionalBonus;
    }

    public function setFinalAdditionalBonus(?string $finalAdditionalBonus): static
    {
        $this->finalAdditionalBonus = $finalAdditionalBonus;

        return $this;
    }

    public function getLoyaltyAddition(): ?string
    {
        return $this->loyaltyAddition;
    }

    public function setLoyaltyAddition(?string $loyaltyAddition): static
    {
        $this->loyaltyAddition = $loyaltyAddition;

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
        return ($this->licPlan ? (string) $this->licPlan : 'N/A') . ' - ' . $this->financialYear;
    }
}
