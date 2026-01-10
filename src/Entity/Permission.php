<?php

namespace App\Entity;

use App\Repository\PermissionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PermissionRepository::class)]
class Permission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Role $role = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Module $module = null;

    #[ORM\Column]
    private ?bool $canView = null;

    #[ORM\Column]
    private ?bool $canCreate = null;

    #[ORM\Column]
    private ?bool $canEdit = null;

    #[ORM\Column]
    private ?bool $canDelete = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): static
    {
        $this->module = $module;

        return $this;
    }

    public function isCanView(): ?bool
    {
        return $this->canView;
    }

    public function setCanView(bool $canView): static
    {
        $this->canView = $canView;

        return $this;
    }

    public function isCanCreate(): ?bool
    {
        return $this->canCreate;
    }

    public function setCanCreate(bool $canCreate): static
    {
        $this->canCreate = $canCreate;

        return $this;
    }

    public function isCanEdit(): ?bool
    {
        return $this->canEdit;
    }

    public function setCanEdit(bool $canEdit): static
    {
        $this->canEdit = $canEdit;

        return $this;
    }

    public function isCanDelete(): ?bool
    {
        return $this->canDelete;
    }

    public function setCanDelete(bool $canDelete): static
    {
        $this->canDelete = $canDelete;

        return $this;
    }
}
