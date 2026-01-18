<?php

namespace App\Entity;

use App\Repository\LicPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: LicPlanRepository::class)]
class LicPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $tableNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $planName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    /**
     * @var Collection<int, CommissionRule>
     */
    #[ORM\OneToMany(targetEntity: CommissionRule::class, mappedBy: 'licPlan')]
    private Collection $commissionRules;

    #[ORM\ManyToOne]
    private ?LicPlanType $planType = null;

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
        $this->commissionRules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTableNumber(): ?string
    {
        return $this->tableNumber;
    }

    public function setTableNumber(string $tableNumber): static
    {
        $this->tableNumber = $tableNumber;

        return $this;
    }

    public function getPlanName(): ?string
    {
        return $this->planName;
    }

    public function setPlanName(string $planName): static
    {
        $this->planName = $planName;

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

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    // display "914 - New Endowment" in the dropdown
    public function __toString(): string
    {
        return $this->tableNumber . ' - ' . $this->planName;
    }

    /**
     * @return Collection<int, CommissionRule>
     */
    public function getCommissionRules(): Collection
    {
        return $this->commissionRules;
    }

    public function addCommissionRule(CommissionRule $commissionRule): static
    {
        if (!$this->commissionRules->contains($commissionRule)) {
            $this->commissionRules->add($commissionRule);
            $commissionRule->setLicPlan($this);
        }

        return $this;
    }

    public function removeCommissionRule(CommissionRule $commissionRule): static
    {
        if ($this->commissionRules->removeElement($commissionRule)) {
            // set the owning side to null (unless already changed)
            if ($commissionRule->getLicPlan() === $this) {
                $commissionRule->setLicPlan(null);
            }
        }

        return $this;
    }

    public function getPlanType(): ?LicPlanType
    {
        return $this->planType;
    }

    public function setPlanType(?LicPlanType $planType): static
    {
        $this->planType = $planType;

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
}
