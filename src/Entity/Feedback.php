<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\FeedbackRepository;

#[ORM\Entity(repositoryClass: FeedbackRepository::class)]
#[ORM\Table(name: 'feedback')]
class Feedback
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
    #[Assert\NotBlank(message: 'Le contenu du feedback ne peut pas etre vide.')]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: 'Le message doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le message ne peut pas depasser {{ limit }} caracteres.'
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

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_envoi = null;

    public function getDate_envoi(): ?\DateTimeInterface
    {
        return $this->date_envoi;
    }

    public function setDate_envoi(?\DateTimeInterface $date_envoi): self
    {
        $this->date_envoi = $date_envoi;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Assert\Type(type: 'bool', message: 'Le champ anonyme doit etre un booleen.')]
    private ?bool $est_anonyme = null;

    public function isEst_anonyme(): ?bool
    {
        return $this->est_anonyme;
    }

    public function setEst_anonyme(?bool $est_anonyme): self
    {
        $this->est_anonyme = $est_anonyme;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive(message: 'L identifiant employe doit etre positif.')]
    private ?int $employe_id = null;

    public function getEmploye_id(): ?int
    {
        return $this->employe_id;
    }

    public function setEmploye_id(?int $employe_id): self
    {
        $this->employe_id = $employe_id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'feedbacks')]
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

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'La categorie est obligatoire.')]
    #[Assert\Choice(
        choices: ['Soutien technique', 'Suggestion', 'Plainte', 'Culture de l\'entreprise', 'Autre'],
        message: 'Categorie invalide.'
    )]
    private ?string $category = null;

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Choice(
        choices: ['nouveau', 'en_cours', 'traite', 'rejete'],
        message: 'Statut invalide.'
    )]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $auto_response = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $auto_response_generated_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $auto_response_sent_at = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getAutoResponse(): ?string
    {
        return $this->auto_response;
    }

    public function setAutoResponse(?string $autoResponse): self
    {
        $this->auto_response = $autoResponse;
        return $this;
    }

    public function getAutoResponseGeneratedAt(): ?\DateTimeInterface
    {
        return $this->auto_response_generated_at;
    }

    public function setAutoResponseGeneratedAt(?\DateTimeInterface $generatedAt): self
    {
        $this->auto_response_generated_at = $generatedAt;
        return $this;
    }

    public function getAutoResponseSentAt(): ?\DateTimeInterface
    {
        return $this->auto_response_sent_at;
    }

    public function setAutoResponseSentAt(?\DateTimeInterface $sentAt): self
    {
        $this->auto_response_sent_at = $sentAt;
        return $this;
    }

    public function getDateEnvoi(): ?\DateTime
    {
        return $this->date_envoi;
    }

    public function setDateEnvoi(?\DateTime $date_envoi): static
    {
        $this->date_envoi = $date_envoi;

        return $this;
    }

    public function isEstAnonyme(): ?bool
    {
        return $this->est_anonyme;
    }

    public function setEstAnonyme(?bool $est_anonyme): static
    {
        $this->est_anonyme = $est_anonyme;

        return $this;
    }

    public function getEmployeId(): ?int
    {
        return $this->employe_id;
    }

    public function setEmployeId(?int $employe_id): static
    {
        $this->employe_id = $employe_id;

        return $this;
    }

}
