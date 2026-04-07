<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FormationRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formation')]
class Formation
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

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'Le sujet ne peut pas être vide.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le sujet doit comporter au moins {{ limit }} caractères.')]
    private ?string $sujet = null;

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(?string $sujet): self
    {
        $this->sujet = $sujet;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'Le nom du formateur ne peut pas être vide.')]
    #[Assert\Length(min: 3, max: 100, minMessage: 'Le nom du formateur doit comporter au moins {{ limit }} caractères.')]
    private ?string $formateur = null;

    public function getFormateur(): ?string
    {
        return $this->formateur;
    }

    public function setFormateur(?string $formateur): self
    {
        $this->formateur = $formateur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'Le type de formation ne peut pas être vide.')]
    #[Assert\Choice(choices: ['En ligne', 'Présentiel', 'Hybride'], message: 'Le type de formation doit être l\'un des suivants : En ligne, Présentiel, Hybride.')]
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

    #[ORM\Column(name: 'date_debut', type: 'date', nullable: true)]
    #[Assert\NotBlank(message: 'La date de début ne peut pas être vide.')]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $dateDebut = null;

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\NotBlank(message: 'La durée ne peut pas être vide.')]
    #[Assert\Positive(message: 'La durée doit être un nombre positif.')]
    private ?int $duree = null;

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): self
    {
        $this->duree = $duree;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'La localisation ne peut pas être vide.')]
    private ?string $localisation = null;

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): self
    {
        $this->localisation = $localisation;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'formations')]
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

    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'formation')]
    private Collection $participations;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        if (!$this->participations instanceof Collection) {
            $this->participations = new ArrayCollection();
        }
        return $this->participations;
    }

    public function addParticipation(Participation $participation): self
    {
        if (!$this->getParticipations()->contains($participation)) {
            $this->getParticipations()->add($participation);
        }
        return $this;
    }

    public function removeParticipation(Participation $participation): self
    {
        $this->getParticipations()->removeElement($participation);
        return $this;
    }
}
