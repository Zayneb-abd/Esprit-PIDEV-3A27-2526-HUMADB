<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\PublicationRepository;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Table(name: 'publication')]
class Publication
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
    #[Assert\NotBlank(message: 'Le contenu de la publication ne peut pas etre vide.')]
    #[Assert\Length(
        min: 10,
        max: 5000,
        minMessage: 'Le contenu doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le contenu ne peut pas depasser {{ limit }} caracteres.'
    )]
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
    #[Assert\Type(type: \DateTimeInterface::class, message: 'La date de publication doit etre valide.')]
    private ?\DateTimeInterface $date_publication = null;

    public function getDate_publication(): ?\DateTimeInterface
    {
        return $this->date_publication;
    }

    public function setDate_publication(?\DateTimeInterface $date_publication): self
    {
        $this->date_publication = $date_publication;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'Le type ne peut pas depasser {{ limit }} caracteres.')]
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'publications')]
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

    /** @var Collection<int, Commentaire> */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'publication')]
    private Collection $commentaires;

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->getCommentaires()->contains($commentaire)) {
            $this->getCommentaires()->add($commentaire);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        $this->getCommentaires()->removeElement($commentaire);
        return $this;
    }

    /** @var Collection<int, PublicationMedia> */
    #[ORM\OneToMany(targetEntity: PublicationMedia::class, mappedBy: 'publication')]
    private Collection $publicationMedias;

    /**
     * @return Collection<int, PublicationMedia>
     */
    public function getPublicationMedias(): Collection
    {
        return $this->publicationMedias;
    }

    public function addPublicationMedia(PublicationMedia $publicationMedia): self
    {
        if (!$this->getPublicationMedias()->contains($publicationMedia)) {
            $this->getPublicationMedias()->add($publicationMedia);
        }
        return $this;
    }

    public function removePublicationMedia(PublicationMedia $publicationMedia): self
    {
        $this->getPublicationMedias()->removeElement($publicationMedia);
        return $this;
    }

    #[ORM\OneToOne(targetEntity: ReactionPublication::class, mappedBy: 'publication', fetch: 'LAZY')]
    private ?ReactionPublication $reactionPublication = null;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->publicationMedias = new ArrayCollection();
    }

    public function getReactionPublication(): ?ReactionPublication
    {
        return $this->reactionPublication;
    }

    public function setReactionPublication(?ReactionPublication $reactionPublication): self
    {
        $this->reactionPublication = $reactionPublication;
        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->date_publication;
    }

    public function setDatePublication(?\DateTime $date_publication): static
    {
        $this->date_publication = $date_publication;

        return $this;
    }

}
