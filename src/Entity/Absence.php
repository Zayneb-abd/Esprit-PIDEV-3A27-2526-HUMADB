<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use App\Repository\AbsenceRepository;

#[ORM\Entity(repositoryClass: AbsenceRepository::class)]
#[ORM\Table(name: 'absence')]
#[Assert\Callback([Absence::class, 'validateDates'])]
#[Assert\Callback([Absence::class, 'validateDureeMax'])]
#[Assert\Callback([Absence::class, 'validateMotifIfMaladie'])]
class Absence
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'absences')]
    #[ORM\JoinColumn(name: 'employe_id', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire.')]
    #[Assert\Date(message: 'La date de début doit être une date valide.')]
    #[Assert\GreaterThanOrEqual('today', message: 'La date de début ne peut pas être dans le passé.')]
    private ?\DateTimeInterface $date_debut = null;

    public function getDate_debut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDate_debut(\DateTimeInterface $date_debut): self
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire.')]
    #[Assert\Date(message: 'La date de fin doit être une date valide.')]
    private ?\DateTimeInterface $date_fin = null;

    public function getDate_fin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDate_fin(?\DateTimeInterface $date_fin): self
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le type d\'absence est obligatoire.')]
    #[Assert\Choice(choices: ['CONGE_PAYE', 'CONGE_SANS_SOLDE', 'MALADIE', 'FORMATION', 'AUTRE'], message: 'Le type d\'absence n\'est pas valide.')]
    private ?string $type_absence = null;

    public function getType_absence(): ?string
    {
        return $this->type_absence;
    }

    public function setType_absence(string $type_absence): self
    {
        $this->type_absence = $type_absence;
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

    #[ORM\OneToOne(targetEntity: Conge::class, mappedBy: 'absence')]
    private ?Conge $conge = null;

    public function getConge(): ?Conge
    {
        return $this->conge;
    }

    public function setConge(?Conge $conge): self
    {
        $this->conge = $conge;
        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function getTypeAbsence(): ?string
    {
        return $this->type_absence;
    }

    public function setTypeAbsence(string $type_absence): static
    {
        $this->type_absence = $type_absence;

        return $this;
    }

    public static function validateDates(self $absence, ExecutionContextInterface $context): void
    {
        $dateDebut = $absence->getDate_debut();
        $dateFin = $absence->getDate_fin();

        if ($dateDebut && $dateFin) {
            // Vérifier que date fin est après date début
            if ($dateFin <= $dateDebut) {
                $context->buildViolation('La date de fin doit être après la date de début.')
                    ->atPath('date_fin')
                    ->addViolation();
            }

            // Vérifier que les dates ne sont pas en 2027 ou après
            $yearDebut = (int)$dateDebut->format('Y');
            $yearFin = (int)$dateFin->format('Y');

            if ($yearDebut > 2026) {
                $context->buildViolation('La date de début ne peut pas être en 2027 ou au-delà.')
                    ->atPath('date_debut')
                    ->addViolation();
            }

            if ($yearFin > 2026) {
                $context->buildViolation('La date de fin ne peut pas être en 2027 ou au-delà.')
                    ->atPath('date_fin')
                    ->addViolation();
            }
        }
    }

    public static function validateDureeMax(self $absence, ExecutionContextInterface $context): void
    {
        $dateDebut = $absence->getDate_debut();
        $dateFin = $absence->getDate_fin();

        if ($dateDebut && $dateFin) {
            $diff = $dateDebut->diff($dateFin);
            $dureeJours = $diff->days;

            if ($dureeJours > 30) {
                $context->buildViolation('La durée maximale est de 30 jours.')
                    ->atPath('date_fin')
                    ->addViolation();
            }
        }
    }

    public static function validateMotifIfMaladie(self $absence, ExecutionContextInterface $context): void
    {
        $type = $absence->getType_absence();
        $dateDebut = $absence->getDate_debut();
        $dateFin = $absence->getDate_fin();

        if ($type === 'MALADIE' && $dateDebut && $dateFin) {
            $diff = $dateDebut->diff($dateFin);
            $dureeJours = $diff->days;

            // Si maladie > 3 jours, vérifier le motif dans le congé lié
            if ($dureeJours > 3) {
                $conge = $absence->getConge();
                if ($conge) {
                    $commentaire = $conge->getCommentaire_validation();
                    if (empty($commentaire)) {
                        $context->buildViolation('Le motif est obligatoire pour un congé maladie de plus de 3 jours.')
                            ->atPath('conge.commentaire_validation')
                            ->addViolation();
                    }
                }
            }
        }
    }

}
