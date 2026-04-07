<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReputationRepository;

#[ORM\Entity(repositoryClass: ReputationRepository::class)]
#[ORM\Table(name: 'reputation')]
class Reputation
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

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'reputation')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', unique: true)]
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $total_score = null;

    public function getTotal_score(): ?int
    {
        return $this->total_score;
    }

    public function setTotal_score(?int $total_score): self
    {
        $this->total_score = $total_score;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $badge = null;

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function setBadge(?string $badge): self
    {
        $this->badge = $badge;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $last_updated = null;

    public function getLast_updated(): ?\DateTimeInterface
    {
        return $this->last_updated;
    }

    public function setLast_updated(?\DateTimeInterface $last_updated): self
    {
        $this->last_updated = $last_updated;
        return $this;
    }

    public function getTotalScore(): ?int
    {
        return $this->total_score;
    }

    public function setTotalScore(?int $total_score): static
    {
        $this->total_score = $total_score;

        return $this;
    }

    public function getLastUpdated(): ?\DateTime
    {
        return $this->last_updated;
    }

    public function setLastUpdated(?\DateTime $last_updated): static
    {
        $this->last_updated = $last_updated;

        return $this;
    }

}
