<?php

namespace App\Entity;

use App\Repository\AgencyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgencyRepository::class)]
class Agency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $businessName = null;

    #[ORM\Column(length: 50)]
    private ?string $agencyCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ownerName = null;

    #[ORM\Column(length: 20)]
    private ?string $mobile = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $licBranchCode = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'agency')]
    private Collection $staff;

    public function __construct()
    {
        $this->staff = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    public function setBusinessName(string $businessName): static
    {
        $this->businessName = $businessName;

        return $this;
    }

    public function getAgencyCode(): ?string
    {
        return $this->agencyCode;
    }

    public function setAgencyCode(string $agencyCode): static
    {
        $this->agencyCode = $agencyCode;

        return $this;
    }

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function setOwnerName(?string $ownerName): static
    {
        $this->ownerName = $ownerName;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): static
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getLicBranchCode(): ?string
    {
        return $this->licBranchCode;
    }

    public function setLicBranchCode(?string $licBranchCode): static
    {
        $this->licBranchCode = $licBranchCode;

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

    /**
     * @return Collection<int, User>
     */
    public function getStaff(): Collection
    {
        return $this->staff;
    }

    public function addStaff(User $staff): static
    {
        if (!$this->staff->contains($staff)) {
            $this->staff->add($staff);
            $staff->setAgency($this);
        }

        return $this;
    }

    public function removeStaff(User $staff): static
    {
        if ($this->staff->removeElement($staff)) {
            // set the owning side to null (unless already changed)
            if ($staff->getAgency() === $this) {
                $staff->setAgency(null);
            }
        }

        return $this;
    }
}
