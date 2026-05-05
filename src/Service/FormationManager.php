<?php

namespace App\Service;

use App\Entity\Formation;
use App\Entity\Participation;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des formations avec règles métier
 */
class FormationManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Valide les données d'une formation selon les règles métier
     * 
     * Règles:
     * 1. Titre (sujet) obligatoire et min 3 caractères
     * 2. Formateur obligatoire et min 3 caractères
     * 3. Date valide (non dans le passé)
     * 4. Type obligatoire (En ligne, Présentiel, Hybride)
     * 5. Durée > 0
     * 6. Maximum 20 participants
     *
     * @param Formation $formation
     * @return array Tableau d'erreurs (vide si valid)
     */
    public function validateFormation(Formation $formation): array
    {
        $errors = [];

        // Règle 1: Titre obligatoire
        $sujet = $formation->getSujet();
        if (empty($sujet) || strlen(trim($sujet)) < 3) {
            $errors[] = 'Le titre de la formation est obligatoire et doit contenir au moins 3 caractères.';
        }

        // Règle 2: Formateur obligatoire
        $formateur = $formation->getFormateur();
        if (empty($formateur) || strlen(trim($formateur)) < 3) {
            $errors[] = 'Le nom du formateur est obligatoire et doit contenir au moins 3 caractères.';
        }

        // Règle 3: Date valide (non dans le passé)
        $dateDebut = $formation->getDateDebut();
        if ($dateDebut === null) {
            $errors[] = 'La date de début est obligatoire.';
        } elseif ($dateDebut < new DateTime('today midnight')) {
            $errors[] = 'La date de début ne peut pas être dans le passé.';
        }

        // Règle 4: Type obligatoire
        $type = $formation->getType();
        $validTypes = ['En ligne', 'Présentiel', 'Hybride'];
        if (empty($type) || !in_array($type, $validTypes, true)) {
            $errors[] = 'Le type de formation est obligatoire (En ligne, Présentiel ou Hybride).';
        }

        // Règle 5: Durée > 0
        $duree = $formation->getDuree();
        if ($duree === null || $duree <= 0) {
            $errors[] = 'La durée doit être supérieure à 0.';
        }

        return $errors;
    }

    /**
     * Vérifie si une formation peut accepter de nouveaux participants
     * 
     * Règle métier: Maximum 20 participants par formation
     *
     * @param Formation $formation
     * @return bool
     */
    public function canAcceptParticipants(Formation $formation): bool
    {
        $participations = $formation->getParticipations();
        $count = 0;
        foreach ($participations as $participation) {
            if (in_array($participation->getStatut(), ['accepté', 'en attente'], true)) {
                $count++;
            }
        }
        
        return $count < 20;
    }

    /**
     * Compte le nombre de places disponibles
     *
     * @param Formation $formation
     * @return int
     */
    public function getAvailablePlaces(Formation $formation): int
    {
        $participations = $formation->getParticipations();
        $count = 0;
        foreach ($participations as $participation) {
            if (in_array($participation->getStatut(), ['accepté', 'en attente'], true)) {
                $count++;
            }
        }
        
        return max(0, 20 - $count);
    }

    /**
     * Crée une formation après validation
     *
     * @param Formation $formation
     * @return array ['success' => bool, 'errors' => array, 'formation' => Formation|null]
     */
    public function createFormation(Formation $formation): array
    {
        $errors = $this->validateFormation($formation);
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'formation' => null
            ];
        }

        $this->entityManager->persist($formation);
        $this->entityManager->flush();

        return [
            'success' => true,
            'errors' => [],
            'formation' => $formation
        ];
    }

    /**
     * Vérifie si une formation peut être modifiée
     * 
     * Règle: Une formation ne peut pas être modifiée si elle a déjà commencé
     *
     * @param Formation $formation
     * @return bool
     */
    public function canBeModified(Formation $formation): bool
    {
        $dateDebut = $formation->getDateDebut();
        if ($dateDebut === null) {
            return true;
        }
        
        return $dateDebut >= new DateTime('today midnight');
    }

    /**
     * Vérifie si une formation est complète
     *
     * @param Formation $formation
     * @return bool
     */
    public function isFull(Formation $formation): bool
    {
        return $this->getAvailablePlaces($formation) === 0;
    }
}
