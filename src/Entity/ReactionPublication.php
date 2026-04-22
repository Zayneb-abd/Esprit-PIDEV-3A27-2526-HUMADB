<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReactionPublicationRepository;

#[ORM\Entity(repositoryClass: ReactionPublicationRepository::class)]
#[ORM\Table(name: 'reaction_publication')]
class ReactionPublication
{
    // Types de réactions disponibles
    const TYPE_LIKE = 'like';
    const TYPE_DISLIKE = 'dislike';
    
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_LIKE => '👍 Like',
            self::TYPE_DISLIKE => '👎 Dislike'
        ];
    }

    public static function getEmojiForType(string $type): string
    {
        return match($type) {
            self::TYPE_LIKE => '👍',
            self::TYPE_DISLIKE => '👎',
            default => '👍'
        };
    }

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

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'reactionPublications')]
    #[ORM\JoinColumn(name: 'publication_id', referencedColumnName: 'id', nullable: false)]
    private ?Publication $publication = null;

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): self
    {
        $this->publication = $publication;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
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

    #[ORM\Column(type: 'string', length: 20, nullable: false)]
    #[Assert\Choice(choices: [self::TYPE_LIKE, self::TYPE_DISLIKE], message: 'Le type de réaction doit être like ou dislike')]
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function isLike(): bool
    {
        return $this->type === self::TYPE_LIKE;
    }

    public function isDislike(): bool
    {
        return $this->type === self::TYPE_DISLIKE;
    }

    public function getEmoji(): string
    {
        return self::getEmojiForType($this->type ?? 'like');
    }

    public function __toString(): string
    {
        return $this->getEmoji() . ' ' . ucfirst($this->type);
    }

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }
}
