<?php

namespace App\Service;

use App\Entity\Formation;
use App\Entity\Participation;
use App\Entity\User;
use App\Repository\ParticipationRepository;
use App\Service\FormationManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des participations avec règles métier
 */
class ParticipationManager
{
    public function __construct(
        private ParticipationRepository $participationRepository,
        private FormationManager $formationManager,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Valide une participation selon les règles métier
     * 
     * Règles:
     * 1. Statut obligatoire et valide
     * 2. Formation obligatoire et non nulle
     * 3. Formation non complète (places disponibles)
     * 4. Utilisateur non déjà inscrit à cette formation
     * 5. Date d'inscription valide
     *
     * @param Participation $participation
     * @param User|null $user
     * @return array Tableau d'erreurs (vide si valid)
     */
    public function validateParticipation(Participation $participation, ?User $user = null): array
    {
        $errors = [];

        // Règle 1: Statut obligatoire et valide
        $statut = $participation->getStatut();
        $validStatuses = ['en attente', 'accepté', 'refusé', 'complété'];
        if (empty($statut) || !in_array($statut, $validStatuses, true)) {
            $errors[] = 'Le statut est obligatoire et doit être l\'un des suivants : en attente, accepté, refusé, complété.';
        }

        // Règle 2: Formation obligatoire
        $formation = $participation->getFormation();
        if ($formation === null) {
            $errors[] = 'La formation est obligatoire.';
        } else {
            // Règle 3: Formation non complète
            if (!$this->formationManager->canAcceptParticipants($formation)) {
                $errors[] = 'Cette formation est complète (maximum 20 participants atteint).';
            }

            // Règle 4: Vérifier si l'utilisateur est déjà inscrit
            if ($user !== null && $this->isUserAlreadyRegistered($user, $formation)) {
                $errors[] = 'Vous êtes déjà inscrit à cette formation.';
            }
        }

        // Règle 5: Date d'inscription valide
        $dateInscription = $participation->getDateInscription();
        if ($dateInscription === null) {
            $errors[] = 'La date d\'inscription est obligatoire.';
        } elseif ($dateInscription > new DateTime()) {
            $errors[] = 'La date d\'inscription ne peut pas être dans le futur.';
        }

        return $errors;
    }

    /**
     * Vérifie si un utilisateur est déjà inscrit à une formation
     *
     * @param User $user
     * @param Formation $formation
     * @return bool
     */
    public function isUserAlreadyRegistered(User $user, Formation $formation): bool
    {
        $participations = $this->participationRepository->findBy([
            'user' => $user,
            'formation' => $formation
        ]);

        foreach ($participations as $participation) {
            if (!in_array($participation->getStatut(), ['refusé'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Crée une participation après validation
     *
     * @param Participation $participation
     * @param User|null $user
     * @return array ['success' => bool, 'errors' => array, 'participation' => Participation|null]
     */
    public function createParticipation(Participation $participation, ?User $user = null): array
    {
        $errors = $this->validateParticipation($participation, $user);
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'participation' => null
            ];
        }

        // Définir la date d'inscription si non définie
        if ($participation->getDateInscription() === null) {
            $participation->setDateInscription(new DateTime());
        }

        $this->entityManager->persist($participation);
        $this->entityManager->flush();

        return [
            'success' => true,
            'errors' => [],
            'participation' => $participation
        ];
    }

    /**
     * Change le statut d'une participation
     * 
     * Règle: Ne peut pas passer à "accepté" si la formation est complète
     *
     * @param Participation $participation
     * @param string $newStatut
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function changeStatus(Participation $participation, string $newStatut): array
    {
        $validStatuses = ['en attente', 'accepté', 'refusé', 'complété'];
        
        if (!in_array($newStatut, $validStatuses, true)) {
            return [
                'success' => false,
                'error' => 'Statut invalide.'
            ];
        }

        // Vérifier si on peut accepter cette participation
        if ($newStatut === 'accepté') {
            $formation = $participation->getFormation();
            if ($formation !== null && !$this->formationManager->canAcceptParticipants($formation)) {
                // Vérifier si cette participation était déjà acceptée
                if ($participation->getStatut() !== 'accepté') {
                    return [
                        'success' => false,
                        'error' => 'Impossible d\'accepter cette participation : la formation est complète.'
                    ];
                }
            }
        }

        $participation->setStatut($newStatut);
        $this->entityManager->persist($participation);
        $this->entityManager->flush();

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Vérifie si une participation peut être annulée
     * 
     * Règle: Ne peut pas annuler si la formation a déjà commencé
     *
     * @param Participation $participation
     * @return bool
     */
    public function canBeCancelled(Participation $participation): bool
    {
        $formation = $participation->getFormation();
        if ($formation === null) {
            return true;
        }

        $dateDebut = $formation->getDateDebut();
        if ($dateDebut === null) {
            return true;
        }

        return $dateDebut >= new DateTime('today midnight');
    }

    /**
     * Récupère les participations actives d'une formation
     *
     * @param Formation $formation
     * @return array
     */
    public function getActiveParticipations(Formation $formation): array
    {
        return $this->participationRepository->findBy([
            'formation' => $formation,
            'statut' => ['en attente', 'accepté']
        ]);
    }
}
