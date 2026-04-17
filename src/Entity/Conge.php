<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use App\Repository\CongeRepository;

#[ORM\Entity(repositoryClass: CongeRepository::class)]
#[ORM\Table(name: 'conge')]
#[Assert\Callback([Conge::class, 'validateDateDemande'])]
class Conge
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

    #[ORM\OneToOne(targetEntity: Absence::class, inversedBy: 'conge')]
    #[ORM\JoinColumn(name: 'absence_id', referencedColumnName: 'id', unique: true)]
    private ?Absence $absence = null;

    public function getAbsence(): ?Absence
    {
        return $this->absence;
    }

    public function setAbsence(?Absence $absence): self
    {
        $this->absence = $absence;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotBlank(message: 'La date de demande est obligatoire.')]
    #[Assert\Date(message: 'La date de demande doit être une date valide.')]
    #[Assert\GreaterThanOrEqual('today', message: 'La date de demande ne peut pas être dans le passé.')]
    private ?\DateTimeInterface $date_demande = null;

    public function getDate_demande(): ?\DateTimeInterface
    {
        return $this->date_demande;
    }

    public function setDate_demande(\DateTimeInterface $date_demande): self
    {
        $this->date_demande = $date_demande;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'conges')]
    #[ORM\JoinColumn(name: 'manager_id', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire_validation = null;

    public function getCommentaire_validation(): ?string
    {
        return $this->commentaire_validation;
    }

    public function setCommentaire_validation(?string $commentaire_validation): self
    {
        $this->commentaire_validation = $commentaire_validation;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_validation = null;

    public function getDate_validation(): ?\DateTimeInterface
    {
        return $this->date_validation;
    }

    public function setDate_validation(?\DateTimeInterface $date_validation): self
    {
        $this->date_validation = $date_validation;
        return $this;
    }

    public function getDateDemande(): ?\DateTime
    {
        return $this->date_demande;
    }

    public function setDateDemande(\DateTime $date_demande): static
    {
        $this->date_demande = $date_demande;

        return $this;
    }

    public function getCommentaireValidation(): ?string
    {
        return $this->commentaire_validation;
    }

    public function setCommentaireValidation(?string $commentaire_validation): static
    {
        $this->commentaire_validation = $commentaire_validation;

        return $this;
    }

    public function getDateValidation(): ?\DateTime
    {
        return $this->date_validation;
    }

    public function setDateValidation(?\DateTime $date_validation): static
    {
        $this->date_validation = $date_validation;

        return $this;
    }

    public static function validateDateDemande(self $conge, ExecutionContextInterface $context): void
    {
        $dateDemande = $conge->getDate_demande();

        if ($dateDemande) {
            // Vérifier que l'année n'est pas en 2027 ou après
            $year = (int)$dateDemande->format('Y');

            if ($year > 2026) {
                $context->buildViolation('La date de demande ne peut pas être en 2027 ou au-delà.')
                    ->atPath('date_demande')
                    ->addViolation();
            }
        }
    }

}
