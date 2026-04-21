<?php

namespace App\WorkflowBundle\Entity;

use App\Entity\Conge;
use App\Entity\Absence;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'approval_history')]
class ApprovalHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    
    #[ORM\ManyToOne(targetEntity: Conge::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Conge $conge = null;
    
    #[ORM\ManyToOne(targetEntity: Absence::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Absence $absence = null;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $approver = null;
    
    #[ORM\Column(type: 'string', length: 20)]
    private string $action; // 'approve' or 'reject'
    
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;
    
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
    
    public function getConge(): ?Conge
    {
        return $this->conge;
    }
    
    public function setConge(?Conge $conge): self
    {
        $this->conge = $conge;
        return $this;
    }
    
    public function getAbsence(): ?Absence
    {
        return $this->absence;
    }
    
    public function setAbsence(?Absence $absence): self
    {
        $this->absence = $absence;
        return $this;
    }
    
    public function getApprover(): ?User
    {
        return $this->approver;
    }
    
    public function setApprover(?User $approver): self
    {
        $this->approver = $approver;
        return $this;
    }
    
    public function getAction(): string
    {
        return $this->action;
    }
    
    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }
    
    public function getComment(): ?string
    {
        return $this->comment;
    }
    
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }
    
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
