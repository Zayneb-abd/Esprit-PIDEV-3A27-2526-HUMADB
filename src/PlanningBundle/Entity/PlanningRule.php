<?php

namespace App\PlanningBundle\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'planning_rules')]
class PlanningRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'manager_id', referencedColumnName: 'id')]
    private ?User $manager = null;
    
    #[ORM\Column(type: 'integer')]
    private int $maxSimultaneousAbsences = 2;
    
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private float $maxAbsencePercentage = 30.0;
    
    #[ORM\Column(type: 'boolean')]
    private bool $requireMinimumCoverage = true;
    
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $blockedDates = null;
    
    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;
    
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getManager(): ?User
    {
        return $this->manager;
    }
    
    public function setManager(?User $manager): self
    {
        $this->manager = $manager;
        return $this;
    }
    
    public function getMaxSimultaneousAbsences(): int
    {
        return $this->maxSimultaneousAbsences;
    }
    
    public function setMaxSimultaneousAbsences(int $max): self
    {
        $this->maxSimultaneousAbsences = $max;
        return $this;
    }
    
    public function getMaxAbsencePercentage(): float
    {
        return $this->maxAbsencePercentage;
    }
    
    public function setMaxAbsencePercentage(float $percentage): self
    {
        $this->maxAbsencePercentage = $percentage;
        return $this;
    }
    
    public function isRequireMinimumCoverage(): bool
    {
        return $this->requireMinimumCoverage;
    }
    
    public function setRequireMinimumCoverage(bool $required): self
    {
        $this->requireMinimumCoverage = $required;
        return $this;
    }
    
    public function getBlockedDates(): ?array
    {
        return $this->blockedDates;
    }
    
    public function setBlockedDates(?array $dates): self
    {
        $this->blockedDates = $dates;
        return $this;
    }
}
