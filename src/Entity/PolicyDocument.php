<?php

namespace App\Entity;

use App\Repository\PolicyDocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: PolicyDocumentRepository::class)]
#[Vich\Uploadable]
class PolicyDocument
{
    // Document Type constants
    public const TYPE_POLICY_BOND       = 'POLICY_BOND';
    public const TYPE_REVIVAL_LETTER    = 'REVIVAL_LETTER';
    public const TYPE_ASSIGNMENT_DEED   = 'ASSIGNMENT_DEED';
    public const TYPE_DISCHARGE_VOUCHER = 'DISCHARGE_VOUCHER';
    public const TYPE_CLAIM_FORM        = 'CLAIM_FORM';
    public const TYPE_OTHER             = 'OTHER';

    public const DOCUMENT_TYPES = [
        self::TYPE_POLICY_BOND       => 'Policy Bond',
        self::TYPE_REVIVAL_LETTER    => 'Revival Letter',
        self::TYPE_ASSIGNMENT_DEED   => 'Assignment Deed',
        self::TYPE_DISCHARGE_VOUCHER => 'Discharge Voucher',
        self::TYPE_CLAIM_FORM        => 'Claim Form',
        self::TYPE_OTHER             => 'Other',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Policy $policy = null;

    #[ORM\Column(length: 50)]
    private ?string $documentType = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(length: 500)]
    private ?string $filePath = null;

    // Virtual property for VichUploader - NOT persisted to DB
    #[Vich\UploadableField(mapping: 'policy_documents', fileNameProperty: 'filePath')]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['application/pdf', 'image/jpeg', 'image/png'],
        mimeTypesMessage: 'Please upload a valid PDF, JPG, or PNG file.',
        maxSizeMessage: 'The file is too large. Maximum allowed size is 10 MB.'
    )]
    private ?File $documentFile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $uploadedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Gedmo\Blameable(on: 'create')]
    private ?string $uploadedBy = null;

    // ── Getters & Setters ──────────────────────────────────────────

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

    public function getDocumentType(): ?string
    {
        return $this->documentType;
    }

    public function setDocumentType(string $documentType): static
    {
        $this->documentType = $documentType;
        return $this;
    }

    public function getDocumentTypeLabel(): string
    {
        return self::DOCUMENT_TYPES[$this->documentType] ?? ($this->documentType ?? '-');
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getDocumentFile(): ?File
    {
        return $this->documentFile;
    }

    public function setDocumentFile(?File $documentFile = null): void
    {
        $this->documentFile = $documentFile;

        // Vich needs at least one mapped column to change so Doctrine
        // detects an update. We bump uploadedAt on every new upload.
        if ($documentFile !== null) {
            $this->uploadedAt = new \DateTime();
        }
    }

    public function getUploadedAt(): ?\DateTimeInterface
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(?\DateTimeInterface $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;
        return $this;
    }

    public function getUploadedBy(): ?string
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?string $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;
        return $this;
    }

    public function __toString(): string
    {
        $label = $this->getDocumentTypeLabel();
        if ($this->fileName) {
            $label .= ' — ' . $this->fileName;
        }
        return $label;
    }
}
