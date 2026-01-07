<?php

namespace App\Entity;

use App\Repository\LicPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

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

    #[ORM\Column(length: 50)]
    private ?string $type = null;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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
}
