<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\QuestionRepository;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: 'question')]
class Question
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

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'questions')]
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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $question_text = null;

    public function getQuestion_text(): ?string
    {
        return $this->question_text;
    }

    public function setQuestion_text(?string $question_text): self
    {
        $this->question_text = $question_text;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $points = null;

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): self
    {
        $this->points = $points;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ReponseQcm::class, mappedBy: 'question')]
    private Collection $reponseQcms;

    public function __construct()
    {
        $this->reponseQcms = new ArrayCollection();
    }

    /**
     * @return Collection<int, ReponseQcm>
     */
    public function getReponseQcms(): Collection
    {
        if (!$this->reponseQcms instanceof Collection) {
            $this->reponseQcms = new ArrayCollection();
        }
        return $this->reponseQcms;
    }

    public function addReponseQcm(ReponseQcm $reponseQcm): self
    {
        if (!$this->getReponseQcms()->contains($reponseQcm)) {
            $this->getReponseQcms()->add($reponseQcm);
        }
        return $this;
    }

    public function removeReponseQcm(ReponseQcm $reponseQcm): self
    {
        $this->getReponseQcms()->removeElement($reponseQcm);
        return $this;
    }

    public function getQuestionText(): ?string
    {
        return $this->question_text;
    }

    public function setQuestionText(?string $question_text): static
    {
        $this->question_text = $question_text;

        return $this;
    }

}
