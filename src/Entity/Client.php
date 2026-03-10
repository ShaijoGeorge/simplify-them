<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[UniqueEntity(
    fields: ['mobile', 'agency'], 
    errorPath: 'mobile', 
    message: 'This mobile number is already registered in your agency.'
)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dob = null;

    #[ORM\Column(length: 20)]
    private ?string $mobile = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $pincode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private ?string $walletBalance = '0.00';

    #[ORM\ManyToOne(inversedBy: 'clients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agency $agency = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'familyMembers')]
    private ?self $headOfFamily = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'headOfFamily')]
    private Collection $familyMembers;

    /**
     * @var Collection<int, Policy>
     */
    #[ORM\OneToMany(targetEntity: Policy::class, mappedBy: 'client')]
    private Collection $policies;

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
        $this->familyMembers = new ArrayCollection();
        $this->policies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDob(): ?\DateTime
    {
        return $this->dob;
    }

    public function setDob(?\DateTime $dob): static
    {
        $this->dob = $dob;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPincode(): ?string
    {
        return $this->pincode;
    }

    public function setPincode(?string $pincode): static
    {
        $this->pincode = $pincode;

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

    public function getHeadOfFamily(): ?self
    {
        return $this->headOfFamily;
    }

    public function setHeadOfFamily(?self $headOfFamily): static
    {
        $this->headOfFamily = $headOfFamily;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFamilyMembers(): Collection
    {
        return $this->familyMembers;
    }

    public function addFamilyMember(self $familyMember): static
    {
        if (!$this->familyMembers->contains($familyMember)) {
            $this->familyMembers->add($familyMember);
            $familyMember->setHeadOfFamily($this);
        }

        return $this;
    }

    public function removeFamilyMember(self $familyMember): static
    {
        if ($this->familyMembers->removeElement($familyMember)) {
            // set the owning side to null (unless already changed)
            if ($familyMember->getHeadOfFamily() === $this) {
                $familyMember->setHeadOfFamily(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Policy>
     */
    public function getPolicies(): Collection
    {
        return $this->policies;
    }

    public function addPolicy(Policy $policy): static
    {
        if (!$this->policies->contains($policy)) {
            $this->policies->add($policy);
            $policy->setClient($this);
        }

        return $this;
    }

    public function removePolicy(Policy $policy): static
    {
        if ($this->policies->removeElement($policy)) {
            // set the owning side to null (unless already changed)
            if ($policy->getClient() === $this) {
                $policy->setClient(null);
            }
        }

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

    public function getFullName(): string
    {
        return $this->name ?? '';
    }

    // Wallet Balance

    public function getWalletBalance(): ?string
    {
        return $this->walletBalance;
    }

    public function setWalletBalance(?string $walletBalance): static
    {
        $this->walletBalance = $walletBalance;
        return $this;
    }

    /**
     * Adjust the wallet balance by a delta amount.
     * Positive delta = agency owes client more (client overpaid).
     * Negative delta = client owes agency more (agent covered shortfall/fine).
     */
    public function adjustWalletBalance(float $delta): static
    {
        $current = (float) ($this->walletBalance ?? 0);
        $this->walletBalance = (string) round($current + $delta, 2);
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
