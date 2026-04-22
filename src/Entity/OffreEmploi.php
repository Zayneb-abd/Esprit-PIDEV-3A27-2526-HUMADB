<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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
        }
        return $this;
    }

    public function removeCandidature(Candidature $candidature): self
    {
        $this->getCandidatures()->removeElement($candidature);
        return $this;
    }

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
        }
        return $this;
    }

    public function removeQuiz(Quiz $quiz): self
    {
        $this->getQuizs()->removeElement($quiz);
        return $this;
    }

    public function getDatePublication(): ?\DateTime
    {
        return $this->date_publication;
    }

    public function setDatePublication(?\DateTime $date_publication): static
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
