<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EntretienRepository;

#[ORM\Entity(repositoryClass: EntretienRepository::class)]
#[ORM\Table(name: 'entretien')]
class Entretien
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

    #[ORM\ManyToOne(targetEntity: Candidature::class, inversedBy: 'entretiens')]
    #[ORM\JoinColumn(name: 'candidature_id', referencedColumnName: 'id')]
    private ?Candidature $candidature = null;

    public function getCandidature(): ?Candidature
    {
        return $this->candidature;
    }

    public function setCandidature(?Candidature $candidature): self
    {
        $this->candidature = $candidature;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'entretiens')]
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'entretiensManager')]
    #[ORM\JoinColumn(name: 'manager_id', referencedColumnName: 'id')]
    private ?User $manager = null;

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(?User $manager): static
    {
        $this->manager = $manager;
        return $this;
    }
    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_entretien = null;

    public function getDate_entretien(): ?\DateTimeInterface
    {
        return $this->date_entretien;
    }

    public function setDate_entretien(\DateTimeInterface $date_entretien): self
    {
        $this->date_entretien = $date_entretien;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duree_minutes = null;

    public function getDuree_minutes(): ?int
    {
        return $this->duree_minutes;
    }

    public function setDuree_minutes(?int $duree_minutes): self
    {
        $this->duree_minutes = $duree_minutes;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $meet_link = null;

    public function getMeet_link(): ?string
    {
        return $this->meet_link;
    }

    public function setMeet_link(?string $meet_link): self
    {
        $this->meet_link = $meet_link;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getDateEntretien(): ?\DateTime
    {
        return $this->date_entretien;
    }

    public function setDateEntretien(\DateTime $date_entretien): static
    {
        $this->date_entretien = $date_entretien;

        return $this;
    }

    public function getDureeMinutes(): ?int
    {
        return $this->duree_minutes;
    }

    public function setDureeMinutes(?int $duree_minutes): static
    {
        $this->duree_minutes = $duree_minutes;

        return $this;
    }

    public function getMeetLink(): ?string
    {
        return $this->meet_link;
    }

    public function setMeetLink(?string $meet_link): static
    {
        $this->meet_link = $meet_link;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

}
