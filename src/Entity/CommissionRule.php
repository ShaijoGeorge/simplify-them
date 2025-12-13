<?php

namespace App\Entity;

use App\Repository\CommissionRuleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommissionRuleRepository::class)]
class CommissionRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commissionRules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?LicPlan $licPlan = null;

    #[ORM\Column]
    private ?int $policyYearFrom = null;

    #[ORM\Column]
    private ?int $policyYearTo = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $commissionRate = null;

    #[ORM\Column(nullable: true)]
    private ?int $minTerm = 0;

    #[ORM\Column(nullable: true)]
    private ?int $maxTerm = 0;

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

    public function getPolicyYearFrom(): ?int
    {
        return $this->policyYearFrom;
    }

    public function setPolicyYearFrom(int $policyYearFrom): static
    {
        $this->policyYearFrom = $policyYearFrom;

        return $this;
    }

    public function getPolicyYearTo(): ?int
    {
        return $this->policyYearTo;
    }

    public function setPolicyYearTo(int $policyYearTo): static
    {
        $this->policyYearTo = $policyYearTo;

        return $this;
    }

    public function getCommissionRate(): ?string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(string $commissionRate): static
    {
        $this->commissionRate = $commissionRate;

        return $this;
    }

    public function getMinTerm(): ?int
    {
        return $this->minTerm;
    }

    public function setMinTerm(?int $minTerm): static
    {
        $this->minTerm = $minTerm;

        return $this;
    }

    public function getMaxTerm(): ?int
    {
        return $this->maxTerm;
    }

    public function setMaxTerm(?int $maxTerm): static
    {
        $this->maxTerm = $maxTerm;

        return $this;
    }
}
