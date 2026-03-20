<?php

namespace App\Entity;

use App\Repository\PolicyStatusLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PolicyStatusLogRepository::class)]
#[ORM\Index(columns: ['policy_id'], name: 'idx_status_log_policy')]
#[ORM\Index(columns: ['transitioned_at'], name: 'idx_status_log_date')]
#[ORM\Index(columns: ['new_status'], name: 'idx_status_log_new_status')]
class PolicyStatusLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Policy::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Policy $policy = null;

    #[ORM\Column(length: 20)]
    private ?string $oldStatus = null;

    #[ORM\Column(length: 20)]
    private ?string $newStatus = null;

    #[ORM\Column(length: 50)]
    private ?string $triggeredBy = null; // e.g. 'app:detect-lapses', 'manual'

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $paidUpSumAssured = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $transitionedAt = null;

    public function __construct()
    {
        $this->transitionedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOldStatus(): ?string
    {
        return $this->oldStatus;
    }

    public function setOldStatus(string $oldStatus): static
    {
        $this->oldStatus = $oldStatus;
        return $this;
    }

    public function getNewStatus(): ?string
    {
        return $this->newStatus;
    }

    public function setNewStatus(string $newStatus): static
    {
        $this->newStatus = $newStatus;
        return $this;
    }

    public function getTriggeredBy(): ?string
    {
        return $this->triggeredBy;
    }

    public function setTriggeredBy(string $triggeredBy): static
    {
        $this->triggeredBy = $triggeredBy;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getPaidUpSumAssured(): ?string
    {
        return $this->paidUpSumAssured;
    }

    public function setPaidUpSumAssured(?string $paidUpSumAssured): static
    {
        $this->paidUpSumAssured = $paidUpSumAssured;
        return $this;
    }

    public function getTransitionedAt(): ?\DateTime
    {
        return $this->transitionedAt;
    }

    public function setTransitionedAt(\DateTime $transitionedAt): static
    {
        $this->transitionedAt = $transitionedAt;
        return $this;
    }
}
