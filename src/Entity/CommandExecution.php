<?php

namespace App\Entity;

use App\Repository\CommandExecutionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandExecutionRepository::class)]
#[ORM\Index(columns: ['command_name'], name: 'idx_cmd_name')]
#[ORM\Index(columns: ['executed_at'], name: 'idx_cmd_executed_at')]
class CommandExecution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $commandName = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null; // 'success', 'failed', 'running'

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $output = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $executedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationMs = null;

    #[ORM\Column(nullable: true)]
    private ?int $exitCode = null;

    public function __construct()
    {
        $this->executedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommandName(): ?string
    {
        return $this->commandName;
    }

    public function setCommandName(string $commandName): static
    {
        $this->commandName = $commandName;
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

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function setOutput(?string $output): static
    {
        $this->output = $output;
        return $this;
    }

    public function getExecutedAt(): ?\DateTime
    {
        return $this->executedAt;
    }

    public function setExecutedAt(\DateTime $executedAt): static
    {
        $this->executedAt = $executedAt;
        return $this;
    }

    public function getDurationMs(): ?int
    {
        return $this->durationMs;
    }

    public function setDurationMs(?int $durationMs): static
    {
        $this->durationMs = $durationMs;
        return $this;
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function setExitCode(?int $exitCode): static
    {
        $this->exitCode = $exitCode;
        return $this;
    }
}
