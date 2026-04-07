<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReponseQcmRepository;

#[ORM\Entity(repositoryClass: ReponseQcmRepository::class)]
#[ORM\Table(name: 'reponse_qcm')]
class ReponseQcm
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

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'reponseQcms')]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id')]
    private ?Question $question = null;

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $texte = null;

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(?string $texte): self
    {
        $this->texte = $texte;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $correcte = null;

    public function isCorrecte(): ?bool
    {
        return $this->correcte;
    }

    public function setCorrecte(?bool $correcte): self
    {
        $this->correcte = $correcte;
        return $this;
    }

}
