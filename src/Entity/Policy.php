<?php

namespace App\Entity;

use App\Repository\PolicyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PolicyRepository::class)]
class Policy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $policyNumber = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $commencementDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $sumAssured = null;

    #[ORM\Column]
    private ?int $policyTerm = null;

    #[ORM\Column]
    private ?int $premiumPayingTerm = null;

    #[ORM\Column(length: 20)]
    private ?string $premiumMode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $basicPremium = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $gst = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPremium = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $nextDueDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $maturityDate = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?LicPlan $licPlan = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agency $agency = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPolicyNumber(): ?string
    {
        return $this->policyNumber;
    }

    public function setPolicyNumber(string $policyNumber): static
    {
        $this->policyNumber = $policyNumber;

        return $this;
    }

    public function getCommencementDate(): ?\DateTime
    {
        return $this->commencementDate;
    }

    public function setCommencementDate(\DateTime $commencementDate): static
    {
        $this->commencementDate = $commencementDate;

        return $this;
    }

    public function getSumAssured(): ?string
    {
        return $this->sumAssured;
    }

    public function setSumAssured(string $sumAssured): static
    {
        $this->sumAssured = $sumAssured;

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

    public function getPremiumPayingTerm(): ?int
    {
        return $this->premiumPayingTerm;
    }

    public function setPremiumPayingTerm(int $premiumPayingTerm): static
    {
        $this->premiumPayingTerm = $premiumPayingTerm;

        return $this;
    }

    public function getPremiumMode(): ?string
    {
        return $this->premiumMode;
    }

    public function setPremiumMode(string $premiumMode): static
    {
        $this->premiumMode = $premiumMode;

        return $this;
    }

    public function getBasicPremium(): ?string
    {
        return $this->basicPremium;
    }

    public function setBasicPremium(string $basicPremium): static
    {
        $this->basicPremium = $basicPremium;

        return $this;
    }

    public function getGst(): ?string
    {
        return $this->gst;
    }

    public function setGst(string $gst): static
    {
        $this->gst = $gst;

        return $this;
    }

    public function getTotalPremium(): ?string
    {
        return $this->totalPremium;
    }

    public function setTotalPremium(string $totalPremium): static
    {
        $this->totalPremium = $totalPremium;

        return $this;
    }

    public function getNextDueDate(): ?\DateTime
    {
        return $this->nextDueDate;
    }

    public function setNextDueDate(?\DateTime $nextDueDate): static
    {
        $this->nextDueDate = $nextDueDate;

        return $this;
    }

    public function getMaturityDate(): ?\DateTime
    {
        return $this->maturityDate;
    }

    public function setMaturityDate(?\DateTime $maturityDate): static
    {
        $this->maturityDate = $maturityDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
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

    public function getLicPlan(): ?LicPlan
    {
        return $this->licPlan;
    }

    public function setLicPlan(?LicPlan $licPlan): static
    {
        $this->licPlan = $licPlan;

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
}
