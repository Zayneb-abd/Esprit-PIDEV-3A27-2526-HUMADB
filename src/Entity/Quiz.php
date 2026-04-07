<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\QuizRepository;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
#[ORM\Table(name: 'quiz')]
class Quiz
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

    #[ORM\ManyToOne(targetEntity: OffreEmploi::class, inversedBy: 'quizs')]
    #[ORM\JoinColumn(name: 'offre_id', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $seuil_pourcentage = null;

    public function getSeuil_pourcentage(): ?int
    {
        return $this->seuil_pourcentage;
    }

    public function setSeuil_pourcentage(?int $seuil_pourcentage): self
    {
        $this->seuil_pourcentage = $seuil_pourcentage;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'quiz')]
    private Collection $questions;

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        if (!$this->questions instanceof Collection) {
            $this->questions = new ArrayCollection();
        }
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->getQuestions()->contains($question)) {
            $this->getQuestions()->add($question);
        }
        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        $this->getQuestions()->removeElement($question);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ResultatQuiz::class, mappedBy: 'quiz')]
    private Collection $resultatQuizs;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->resultatQuizs = new ArrayCollection();
    }

    /**
     * @return Collection<int, ResultatQuiz>
     */
    public function getResultatQuizs(): Collection
    {
        if (!$this->resultatQuizs instanceof Collection) {
            $this->resultatQuizs = new ArrayCollection();
        }
        return $this->resultatQuizs;
    }

    public function addResultatQuiz(ResultatQuiz $resultatQuiz): self
    {
        if (!$this->getResultatQuizs()->contains($resultatQuiz)) {
            $this->getResultatQuizs()->add($resultatQuiz);
        }
        return $this;
    }

    public function removeResultatQuiz(ResultatQuiz $resultatQuiz): self
    {
        $this->getResultatQuizs()->removeElement($resultatQuiz);
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

    public function getSeuilPourcentage(): ?int
    {
        return $this->seuil_pourcentage;
    }

    public function setSeuilPourcentage(?int $seuil_pourcentage): static
    {
        $this->seuil_pourcentage = $seuil_pourcentage;

        return $this;
    }

}
