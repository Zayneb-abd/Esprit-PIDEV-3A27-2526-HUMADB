<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\CandidatureRepository;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
#[ORM\Table(name: 'candidature')]
class Candidature
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

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\NotNull(message: 'La date de candidature est obligatoire.')]
    #[Assert\Type(type: \DateTimeInterface::class, message: 'La date de candidature doit etre valide.')]
    private ?\DateTimeInterface $date_candidature = null;

    public function getDate_candidature(): ?\DateTimeInterface
    {
        return $this->date_candidature;
    }

    public function setDate_candidature(?\DateTimeInterface $date_candidature): self
    {
        $this->date_candidature = $date_candidature;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(
        choices: ['En attente', 'En cours', 'Acceptee', 'Refusee'],
        message: 'Le statut de la candidature est invalide.'
    )]
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'candidatures')]
    #[ORM\JoinColumn(name: 'candidat_id', referencedColumnName: 'id')]
    #[Assert\NotNull(message: 'Le candidat est obligatoire.')]
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

    #[ORM\ManyToOne(targetEntity: OffreEmploi::class, inversedBy: 'candidatures')]
    #[ORM\JoinColumn(name: 'offre_id', referencedColumnName: 'id')]
    #[Assert\NotNull(message: "L'offre d'emploi est obligatoire.")]
    private ?OffreEmploi $offreEmploi = null;

    public function getOffreEmploi(): ?OffreEmploi
    {
        return $this->offreEmploi;
    }

    public function setOffreEmploi(?OffreEmploi $offreEmploi): self
    {
        $this->offreEmploi = $offreEmploi;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'Le CV est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le CV doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le CV ne peut pas depasser {{ limit }} caracteres.'
    )]
    private ?string $cv = null;

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(?string $cv): self
    {
        $this->cv = $cv;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\NotNull(message: 'La date de statut est obligatoire.')]
    #[Assert\Type(type: \DateTimeInterface::class, message: 'La date de statut doit etre valide.')]
    private ?\DateTimeInterface $date_statut = null;

    public function getDate_statut(): ?\DateTimeInterface
    {
        return $this->date_statut;
    }

    public function setDate_statut(?\DateTimeInterface $date_statut): self
    {
        $this->date_statut = $date_statut;
        return $this;
    }

    /**
     * @var Collection<int, Entretien>
     */
    #[ORM\OneToMany(targetEntity: Entretien::class, mappedBy: 'candidature')]
    private Collection $entretiens;

    public function __construct()
    {
        $this->entretiens = new ArrayCollection();
    }

    /**
     * @return Collection<int, Entretien>
     */
    public function getEntretiens(): Collection
    {
        if (!$this->entretiens instanceof Collection) {
            $this->entretiens = new ArrayCollection();
        }
        return $this->entretiens;
    }

    public function addEntretien(Entretien $entretien): self
    {
        if (!$this->getEntretiens()->contains($entretien)) {
            $this->getEntretiens()->add($entretien);
            $entretien->setCandidature($this);
        }
        return $this;
    }

    public function removeEntretien(Entretien $entretien): self
    {
        if ($this->getEntretiens()->removeElement($entretien) && $entretien->getCandidature() === $this) {
            $entretien->setCandidature(null);
        }
        return $this;
    }

    public function getDateCandidature(): ?\DateTimeInterface
    {
        return $this->date_candidature;
    }

    public function setDateCandidature(?\DateTimeInterface $date_candidature): static
    {
        $this->date_candidature = $date_candidature;

        return $this;
    }

    public function getDateStatut(): ?\DateTimeInterface
    {
        return $this->date_statut;
    }

    public function setDateStatut(?\DateTimeInterface $date_statut): static
    {
        $this->date_statut = $date_statut;

        return $this;
    }

}
