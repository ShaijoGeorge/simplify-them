<?php

namespace App\Entity;

use App\Repository\PremiumTableRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PremiumTableRepository::class)]
#[ORM\UniqueConstraint(name: 'uq_premium_table_plan_age_term', columns: ['lic_plan_id', 'entry_age', 'policy_term'])]
#[UniqueEntity(fields: ['licPlan', 'entryAge', 'policyTerm'], message: 'A premium rate for this plan, entry age and policy term already exists.')]
class PremiumTable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?LicPlan $licPlan = null;

    #[ORM\Column]
    private ?int $entryAge = null;

    #[ORM\Column]
    private ?int $policyTerm = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private ?string $annualPremiumPerThousand = null;

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

    public function getEntryAge(): ?int
    {
        return $this->entryAge;
    }

    public function setEntryAge(int $entryAge): static
    {
        $this->entryAge = $entryAge;

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

    public function getAnnualPremiumPerThousand(): ?string
    {
        return $this->annualPremiumPerThousand;
    }

    public function setAnnualPremiumPerThousand(string $annualPremiumPerThousand): static
    {
        $this->annualPremiumPerThousand = $annualPremiumPerThousand;

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
        return ($this->licPlan ? (string) $this->licPlan : 'N/A') . ' | Age ' . $this->entryAge . ' | Term ' . $this->policyTerm;
    }
}
