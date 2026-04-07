<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PublicationMediaRepository;

#[ORM\Entity(repositoryClass: PublicationMediaRepository::class)]
#[ORM\Table(name: 'publication_media')]
class PublicationMedia
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

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'publicationMedias')]
    #[ORM\JoinColumn(name: 'publication_id', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'string', nullable: false)]
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

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $path = null;

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

}
