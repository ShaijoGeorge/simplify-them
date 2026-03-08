<?php

namespace App\Entity;

use App\Repository\SaRebateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: SaRebateRepository::class)]
class SaRebate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
    private ?string $minSumAssured = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    private ?string $maxSumAssured = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private ?string $rebatePerThousand = null;

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

    public function getMinSumAssured(): ?string
    {
        return $this->minSumAssured;
    }

    public function setMinSumAssured(string $minSumAssured): static
    {
        $this->minSumAssured = $minSumAssured;
        return $this;
    }

    public function getMaxSumAssured(): ?string
    {
        return $this->maxSumAssured;
    }

    public function setMaxSumAssured(?string $maxSumAssured): static
    {
        $this->maxSumAssured = $maxSumAssured;
        return $this;
    }

    public function getRebatePerThousand(): ?string
    {
        return $this->rebatePerThousand;
    }

    public function setRebatePerThousand(string $rebatePerThousand): static
    {
        $this->rebatePerThousand = $rebatePerThousand;
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
        $min = number_format((float) $this->minSumAssured);
        $max = $this->maxSumAssured ? number_format((float) $this->maxSumAssured) : '∞';
        return '₹' . $min . ' – ₹' . $max . ' → ₹' . $this->rebatePerThousand . '/1000';
    }
}
