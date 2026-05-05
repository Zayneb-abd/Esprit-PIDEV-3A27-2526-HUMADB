<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

use App\Repository\UserRepository;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
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

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le nom ne peut pas etre vide.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit contenir au moins {{ limit }} caracteres.')]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le prenom ne peut pas etre vide.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le prenom doit contenir au moins {{ limit }} caracteres.')]
    private ?string $prenom = null;

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'L email ne peut pas etre vide.')]
    #[Assert\Email(message: 'Veuillez saisir une adresse email valide.')]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $mdp = null;

    public function getMdp(): ?string
    {
        return $this->mdp;
    }

    public function setMdp(string $mdp): self
    {
        $this->mdp = $mdp;
        return $this;
    }

    #[ORM\Column(name: 'role', type: 'string', length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['ADMIN_RH', 'MANAGER', 'EMPLOYE', 'CANDIDAT'],
        message: 'Le role doit etre ADMIN_RH, MANAGER, EMPLOYE ou CANDIDAT.'
    )]
    private ?string $role = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $is_active = true;

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        if ($this->role === 'ADMIN_RH') {
            $roles[] = 'ROLE_ADMIN';
        }

        if ($this->role === 'MANAGER') {
            $roles[] = 'ROLE_MANAGER';
        }

        if ($this->role === 'EMPLOYE') {
            $roles[] = 'ROLE_EMPLOYE';
        }

        if ($this->role === 'CANDIDAT') {
            $roles[] = 'ROLE_CANDIDAT';
        }

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): self
    {
        $this->role = $this->normalizeSecurityRolesToDatabaseRole($roles);

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'users')]
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

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\LessThan('today', message: 'La date de naissance doit etre dans le passe.')]
    private ?\DateTimeInterface $date_naissance = null;

    public function getDate_naissance(): ?\DateTimeInterface
    {
        return $this->date_naissance;
    }

    public function setDate_naissance(?\DateTimeInterface $date_naissance): self
    {
        $this->date_naissance = $date_naissance;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $face_image = null;

    public function getFace_image(): ?string
    {
        return $this->face_image;
    }

    public function setFace_image(?string $face_image): self
    {
        $this->face_image = $face_image;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $cv_filename = null;

    #[Vich\UploadableField(mapping: 'candidate_cv', fileNameProperty: 'cv_filename')]
    private ?File $cvFile = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Ignore]
    private ?string $reset_token = null;

    public function getReset_token(): ?string
    {
        return $this->reset_token;
    }

    public function setReset_token(?string $reset_token): self
    {
        $this->reset_token = $reset_token;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $token_expiry = null;

    public function getToken_expiry(): ?\DateTimeInterface
    {
        return $this->token_expiry;
    }

    public function setToken_expiry(?\DateTimeInterface $token_expiry): self
    {
        $this->token_expiry = $token_expiry;
        return $this;
    }

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'Le score de reputation doit etre positif ou nul.')]
    private int $reputation_score = 0;

    public function getReputationScore(): int
    {
        return $this->reputation_score;
    }

    public function setReputationScore(int $reputation_score): self
    {
        $this->reputation_score = $reputation_score;
        return $this;
    }

    public function getReputationBadge(): string
    {
        if ($this->reputation_score >= 500) {
            return 'Gold';
        }
        if ($this->reputation_score >= 100) {
            return 'Silver';
        }
        return 'Bronze';
    }

    #[ORM\OneToMany(targetEntity: Absence::class, mappedBy: 'user')]
    private Collection $absences;

    /**
     * @return Collection<int, Absence>
     */
    public function getAbsences(): Collection
    {
        if (!$this->absences instanceof Collection) {
            $this->absences = new ArrayCollection();
        }
        return $this->absences;
    }

    public function addAbsence(Absence $absence): self
    {
        if (!$this->getAbsences()->contains($absence)) {
            $this->getAbsences()->add($absence);
        }
        return $this;
    }

    public function removeAbsence(Absence $absence): self
    {
        $this->getAbsences()->removeElement($absence);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Candidature::class, mappedBy: 'user')]
    private Collection $candidatures;

    /**
     * @return Collection<int, Candidature>
     */
    public function getCandidatures(): Collection
    {
        if (!$this->candidatures instanceof Collection) {
            $this->candidatures = new ArrayCollection();
        }
        return $this->candidatures;
    }

    public function addCandidature(Candidature $candidature): self
    {
        if (!$this->getCandidatures()->contains($candidature)) {
            $this->getCandidatures()->add($candidature);
        }
        return $this;
    }

    public function removeCandidature(Candidature $candidature): self
    {
        $this->getCandidatures()->removeElement($candidature);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'user')]
    private Collection $commentaires;

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        if (!$this->commentaires instanceof Collection) {
            $this->commentaires = new ArrayCollection();
        }
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->getCommentaires()->contains($commentaire)) {
            $this->getCommentaires()->add($commentaire);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        $this->getCommentaires()->removeElement($commentaire);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Conge::class, mappedBy: 'user')]
    private Collection $conges;

    /**
     * @return Collection<int, Conge>
     */
    public function getConges(): Collection
    {
        if (!$this->conges instanceof Collection) {
            $this->conges = new ArrayCollection();
        }
        return $this->conges;
    }

    public function addConge(Conge $conge): self
    {
        if (!$this->getConges()->contains($conge)) {
            $this->getConges()->add($conge);
        }
        return $this;
    }

    public function removeConge(Conge $conge): self
    {
        $this->getConges()->removeElement($conge);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Entretien::class, mappedBy: 'user')]
    private Collection $entretiens;

    #[ORM\OneToMany(targetEntity: Entretien::class, mappedBy: 'manager')]
    private Collection $entretiensManager;

    /**
     * @return Collection<int, Entretien>
     */
    public function getEntretiens(): Collection
    {
        if (!$this->entretiens instanceof Collection) {
            $this->entretiens = new ArrayCollection();
        }
        return $this->entretiens;
    }

    public function addEntretien(Entretien $entretien): self
    {
        if (!$this->getEntretiens()->contains($entretien)) {
            $this->getEntretiens()->add($entretien);
        }
        return $this;
    }

    public function removeEntretien(Entretien $entretien): self
    {
        $this->getEntretiens()->removeElement($entretien);
        return $this;
    }

    /**
     * @return Collection<int, Entretien>
     */
    public function getEntretiensManager(): Collection
    {
        if (!$this->entretiensManager instanceof Collection) {
            $this->entretiensManager = new ArrayCollection();
        }
        return $this->entretiensManager;
    }

    public function addEntretienManager(Entretien $entretien): self
    {
        if (!$this->getEntretiensManager()->contains($entretien)) {
            $this->getEntretiensManager()->add($entretien);
        }
        return $this;
    }

    public function removeEntretienManager(Entretien $entretien): self
    {
        $this->getEntretiensManager()->removeElement($entretien);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Feedback::class, mappedBy: 'user')]
    private Collection $feedbacks;

    /**
     * @return Collection<int, Feedback>
     */
    public function getFeedbacks(): Collection
    {
        if (!$this->feedbacks instanceof Collection) {
            $this->feedbacks = new ArrayCollection();
        }
        return $this->feedbacks;
    }

    public function addFeedback(Feedback $feedback): self
    {
        if (!$this->getFeedbacks()->contains($feedback)) {
            $this->getFeedbacks()->add($feedback);
        }
        return $this;
    }

    public function removeFeedback(Feedback $feedback): self
    {
        $this->getFeedbacks()->removeElement($feedback);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Formation::class, mappedBy: 'user')]
    private Collection $formations;

    /**
     * @return Collection<int, Formation>
     */
    public function getFormations(): Collection
    {
        if (!$this->formations instanceof Collection) {
            $this->formations = new ArrayCollection();
        }
        return $this->formations;
    }

    public function addFormation(Formation $formation): self
    {
        if (!$this->getFormations()->contains($formation)) {
            $this->getFormations()->add($formation);
        }
        return $this;
    }

    public function removeFormation(Formation $formation): self
    {
        $this->getFormations()->removeElement($formation);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: OffreEmploi::class, mappedBy: 'user')]
    private Collection $offreEmplois;

    /**
     * @return Collection<int, OffreEmploi>
     */
    public function getOffreEmplois(): Collection
    {
        if (!$this->offreEmplois instanceof Collection) {
            $this->offreEmplois = new ArrayCollection();
        }
        return $this->offreEmplois;
    }

    public function addOffreEmploi(OffreEmploi $offreEmploi): self
    {
        if (!$this->getOffreEmplois()->contains($offreEmploi)) {
            $this->getOffreEmplois()->add($offreEmploi);
        }
        return $this;
    }

    public function removeOffreEmploi(OffreEmploi $offreEmploi): self
    {
        $this->getOffreEmplois()->removeElement($offreEmploi);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $participations;

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): self
    {
        if (!$this->getParticipations()->contains($participation)) {
            $this->getParticipations()->add($participation);
        }
        return $this;
    }

    public function removeParticipation(Participation $participation): self
    {
        $this->getParticipations()->removeElement($participation);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Publication::class, mappedBy: 'user')]
    private Collection $publications;

    /**
     * @return Collection<int, Publication>
     */
    public function getPublications(): Collection
    {
        if (!$this->publications instanceof Collection) {
            $this->publications = new ArrayCollection();
        }
        return $this->publications;
    }

    public function addPublication(Publication $publication): self
    {
        if (!$this->getPublications()->contains($publication)) {
            $this->getPublications()->add($publication);
        }
        return $this;
    }

    public function removePublication(Publication $publication): self
    {
        $this->getPublications()->removeElement($publication);
        return $this;
    }

    #[ORM\OneToOne(targetEntity: Reputation::class, mappedBy: 'user')]
    private ?Reputation $reputation = null;

    public function getReputation(): ?Reputation
    {
        return $this->reputation;
    }

    public function setReputation(?Reputation $reputation): self
    {
        $this->reputation = $reputation;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ResultatQuiz::class, mappedBy: 'user')]
    private Collection $resultatQuizs;

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

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'user')]
    private Collection $users;

    public function __construct()
    {
        $this->absences = new ArrayCollection();
        $this->candidatures = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->conges = new ArrayCollection();
        $this->entretiens = new ArrayCollection();
        $this->entretiensManager = new ArrayCollection();
        $this->feedbacks = new ArrayCollection();
        $this->formations = new ArrayCollection();
        $this->offreEmplois = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->resultatQuizs = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private Collection $notifications;

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        if (!$this->users instanceof Collection) {
            $this->users = new ArrayCollection();
        }
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->getUsers()->contains($user)) {
            $this->getUsers()->add($user);
        }
        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->getUsers()->removeElement($user);
        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(?\DateTime $date_naissance): static
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    public function getFaceImage(): mixed
    {
        return $this->face_image;
    }

    public function setFaceImage(mixed $face_image): static
    {
        $this->face_image = $face_image;

        return $this;
    }

    public function setCvFile(?File $cvFile = null): void
    {
        $this->cvFile = $cvFile;

        if ($cvFile !== null) {
            $this->updated_at = new \DateTimeImmutable();
        }
    }

    public function getCvFile(): ?File
    {
        return $this->cvFile;
    }

    public function getCvFilename(): ?string
    {
        return $this->cv_filename;
    }

    public function setCvFilename(?string $cv_filename): static
    {
        $this->cv_filename = $cv_filename;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->reset_token;
    }

    public function setResetToken(?string $reset_token): static
    {
        $this->reset_token = $reset_token;

        return $this;
    }

    public function getTokenExpiry(): ?\DateTime
    {
        return $this->token_expiry;
    }

    public function setTokenExpiry(?\DateTime $token_expiry): static
    {
        $this->token_expiry = $token_expiry;

        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->cvFile = null;
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'mdp' => $this->mdp,
            'role' => $this->role,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->mdp = $data['mdp'] ?? null;
        $this->role = $data['role'] ?? null;
        $this->cvFile = null;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): string
    {
        return $this->mdp;
    }

    private function normalizeSecurityRolesToDatabaseRole(array $roles): ?string
    {
        foreach ($roles as $role) {
            if (in_array($role, ['ADMIN_RH', 'ROLE_ADMIN'], true)) {
                return 'ADMIN_RH';
            }

            if (in_array($role, ['MANAGER', 'ROLE_MANAGER'], true)) {
                return 'MANAGER';
            }

            if (in_array($role, ['EMPLOYE', 'ROLE_EMPLOYE'], true)) {
                return 'EMPLOYE';
            }

            if (in_array($role, ['CANDIDAT', 'ROLE_CANDIDAT', 'ROLE_CONDIDAT'], true)) {
                return 'CANDIDAT';
            }
        }

        return $this->role;
    }

}
