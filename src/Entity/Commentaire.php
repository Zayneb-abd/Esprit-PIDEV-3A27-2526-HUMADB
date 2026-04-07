<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CommentaireRepository;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ORM\Table(name: 'commentaire')]
class Commentaire
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

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_commentaire = null;

    public function getDate_commentaire(): ?\DateTimeInterface
    {
        return $this->date_commentaire;
    }

    public function setDate_commentaire(?\DateTimeInterface $date_commentaire): self
    {
        $this->date_commentaire = $date_commentaire;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'publication_id', referencedColumnName: 'id')]
    private ?Publication $publication = null;

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): self
    {
        $this->publication = $publication;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
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

    public function getDateCommentaire(): ?\DateTime
    {
        return $this->date_commentaire;
    }

    public function setDateCommentaire(?\DateTime $date_commentaire): static
    {
        $this->date_commentaire = $date_commentaire;

        return $this;
    }

}
