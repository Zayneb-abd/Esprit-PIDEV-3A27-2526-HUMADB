<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ResultatQuizRepository;

#[ORM\Entity(repositoryClass: ResultatQuizRepository::class)]
#[ORM\Table(name: 'resultat_quiz')]
class ResultatQuiz
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'resultatQuizs')]
    #[ORM\JoinColumn(name: 'candidat_id', referencedColumnName: 'id')]
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

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'resultatQuizs')]
    #[ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id')]
    private ?Quiz $quiz = null;

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): self
    {
        $this->quiz = $quiz;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $score_pourcentage = null;

    public function getScore_pourcentage(): ?string
    {
        return $this->score_pourcentage;
    }

    public function setScore_pourcentage(?string $score_pourcentage): self
    {
        $this->score_pourcentage = $score_pourcentage;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $temps_utilise = null;

    public function getTemps_utilise(): ?int
    {
        return $this->temps_utilise;
    }

    public function setTemps_utilise(?int $temps_utilise): self
    {
        $this->temps_utilise = $temps_utilise;
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

    public function getScorePourcentage(): ?string
    {
        return $this->score_pourcentage;
    }

    public function setScorePourcentage(?string $score_pourcentage): static
    {
        $this->score_pourcentage = $score_pourcentage;

        return $this;
    }

    public function getTempsUtilise(): ?int
    {
        return $this->temps_utilise;
    }

    public function setTempsUtilise(?int $temps_utilise): static
    {
        $this->temps_utilise = $temps_utilise;

        return $this;
    }

}
