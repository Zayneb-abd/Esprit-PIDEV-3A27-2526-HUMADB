<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\OffreEmploiRepository;

#[ORM\Entity(repositoryClass: OffreEmploiRepository::class)]
#[ORM\Table(name: 'offre_emploi')]
class OffreEmploi
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
    #[Assert\NotBlank(message: "Le titre de l'offre est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caracteres.",
        maxMessage: "Le titre ne peut pas depasser {{ limit }} caracteres."
    )]
    private ?string $titre = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "La description de l'offre est obligatoire.")]
    #[Assert\Length(
        min: 20,
        max: 5000,
        minMessage: "La description doit contenir au moins {{ limit }} caracteres.",
        maxMessage: "La description ne peut pas depasser {{ limit }} caracteres."
    )]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Le departement est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 120,
        minMessage: "Le departement doit contenir au moins {{ limit }} caracteres.",
        maxMessage: "Le departement ne peut pas depasser {{ limit }} caracteres."
    )]
    private ?string $departement = null;

    public function getDepartement(): ?string
    {
        return $this->departement;
    }

    public function setDepartement(?string $departement): self
    {
        $this->departement = $departement;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\NotNull(message: "La date de publication est obligatoire.")]
    #[Assert\Type(type: \DateTimeInterface::class, message: "La date de publication doit etre valide.")]
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
    #[Assert\NotBlank(message: "Le type de contrat est obligatoire.")]
    #[Assert\Choice(
        choices: ['CDI', 'CDD', 'Stage', 'Freelance', 'Alternance'],
        message: "Le type de contrat n'est pas valide."
    )]
    private ?string $type_contrat = null;

    public function getType_contrat(): ?string
    {
        return $this->type_contrat;
    }

    public function setType_contrat(?string $type_contrat): self
    {
        $this->type_contrat = $type_contrat;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\NotNull(message: "Le nombre de postes est obligatoire.")]
    #[Assert\Positive(message: "Le nombre de postes doit etre strictement positif.")]
    private ?int $nombre_postes = null;

    public function getNombre_postes(): ?int
    {
        return $this->nombre_postes;
    }

    public function setNombre_postes(?int $nombre_postes): self
    {
        $this->nombre_postes = $nombre_postes;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'offreEmplois')]
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

    /**
     * @var Collection<int, Candidature>
     */
    #[ORM\OneToMany(targetEntity: Candidature::class, mappedBy: 'offreEmploi')]
    private Collection $candidatures;

    /**
     * @return Collection<int, Candidature>
     */
    public function getCandidatures(): Collection
    {
        if (!$this->candidatures instanceof Collection) {
            $this->candidatures = new ArrayCollection();
        }
        return $this->candidatures;
    }

    public function addCandidature(Candidature $candidature): self
    {
        if (!$this->getCandidatures()->contains($candidature)) {
            $this->getCandidatures()->add($candidature);
            $candidature->setOffreEmploi($this);
        }
        return $this;
    }

    public function removeCandidature(Candidature $candidature): self
    {
        if ($this->getCandidatures()->removeElement($candidature) && $candidature->getOffreEmploi() === $this) {
            $candidature->setOffreEmploi(null);
        }
        return $this;
    }

    /**
     * @var Collection<int, Quiz>
     */
    #[ORM\OneToMany(targetEntity: Quiz::class, mappedBy: 'offreEmploi')]
    private Collection $quizs;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
        $this->quizs = new ArrayCollection();
    }

    /**
     * @return Collection<int, Quiz>
     */
    public function getQuizs(): Collection
    {
        if (!$this->quizs instanceof Collection) {
            $this->quizs = new ArrayCollection();
        }
        return $this->quizs;
    }

    public function addQuiz(Quiz $quiz): self
    {
        if (!$this->getQuizs()->contains($quiz)) {
            $this->getQuizs()->add($quiz);
            $quiz->setOffreEmploi($this);
        }
        return $this;
    }

    public function removeQuiz(Quiz $quiz): self
    {
        if ($this->getQuizs()->removeElement($quiz) && $quiz->getOffreEmploi() === $this) {
            $quiz->setOffreEmploi(null);
        }
        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->date_publication;
    }

    public function setDatePublication(?\DateTimeInterface $date_publication): static
    {
        $this->date_publication = $date_publication;

        return $this;
    }

    public function getTypeContrat(): ?string
    {
        return $this->type_contrat;
    }

    public function setTypeContrat(?string $type_contrat): static
    {
        $this->type_contrat = $type_contrat;

        return $this;
    }

    public function getNombrePostes(): ?int
    {
        return $this->nombre_postes;
    }

    public function setNombrePostes(?int $nombre_postes): static
    {
        $this->nombre_postes = $nombre_postes;

        return $this;
    }

}
