<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\CandidatureRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
#[ORM\Table(name: 'candidature')]
#[UniqueEntity(
    fields: ['user', 'offreEmploi'],
    message: "Ce candidat a deja une candidature pour cette offre."
)]
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
    #[Assert\NotNull(message: "La date de candidature est obligatoire.")]
    #[Assert\LessThanOrEqual('today', message: "La date de candidature ne peut pas etre dans le futur.")]
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
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(
        choices: ['En attente', 'En cours', 'Acceptee', 'Refusee'],
        message: "Le statut selectionne est invalide."
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
    #[Assert\NotNull(message: "Le candidat est obligatoire.")]
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
    #[Assert\NotBlank(message: "Le CV est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le CV doit contenir au moins {{ limit }} caracteres.",
        maxMessage: "Le CV ne doit pas depasser {{ limit }} caracteres."
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
    #[Assert\NotNull(message: "La date du statut est obligatoire.")]
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
        }
        return $this;
    }

    public function removeEntretien(Entretien $entretien): self
    {
        $this->getEntretiens()->removeElement($entretien);
        return $this;
    }

    public function getDateCandidature(): ?\DateTime
    {
        return $this->date_candidature;
    }

    public function setDateCandidature(?\DateTime $date_candidature): static
    {
        $this->date_candidature = $date_candidature;

        return $this;
    }

    public function getDateStatut(): ?\DateTime
    {
        return $this->date_statut;
    }

    public function setDateStatut(?\DateTime $date_statut): static
    {
        $this->date_statut = $date_statut;

        return $this;
    }

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->date_statut !== null && $this->date_candidature !== null && $this->date_statut < $this->date_candidature) {
            $context->buildViolation("La date du statut doit etre posterieure ou egale a la date de candidature.")
                ->atPath('date_statut')
                ->addViolation();
        }
    }

}
