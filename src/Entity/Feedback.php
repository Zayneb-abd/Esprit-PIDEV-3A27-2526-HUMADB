<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FeedbackRepository;

#[ORM\Entity(repositoryClass: FeedbackRepository::class)]
#[ORM\Table(name: 'feedback')]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $contenu = null;

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_envoi = null;

    public function getDate_envoi(): ?\DateTimeInterface
    {
        return $this->date_envoi;
    }

    public function setDate_envoi(?\DateTimeInterface $date_envoi): self
    {
        $this->date_envoi = $date_envoi;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $est_anonyme = null;

    public function isEst_anonyme(): ?bool
    {
        return $this->est_anonyme;
    }

    public function setEst_anonyme(?bool $est_anonyme): self
    {
        $this->est_anonyme = $est_anonyme;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $employe_id = null;

    public function getEmploye_id(): ?int
    {
        return $this->employe_id;
    }

    public function setEmploye_id(?int $employe_id): self
    {
        $this->employe_id = $employe_id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'feedbacks')]
    #[ORM\JoinColumn(name: 'admin_id', referencedColumnName: 'id')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $category = null;

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDateEnvoi(): ?\DateTime
    {
        return $this->date_envoi;
    }

    public function setDateEnvoi(?\DateTime $date_envoi): static
    {
        $this->date_envoi = $date_envoi;

        return $this;
    }

    public function isEstAnonyme(): ?bool
    {
        return $this->est_anonyme;
    }

    public function setEstAnonyme(?bool $est_anonyme): static
    {
        $this->est_anonyme = $est_anonyme;

        return $this;
    }

    public function getEmployeId(): ?int
    {
        return $this->employe_id;
    }

    public function setEmployeId(?int $employe_id): static
    {
        $this->employe_id = $employe_id;

        return $this;
    }

}
