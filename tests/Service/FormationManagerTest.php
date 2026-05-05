<?php

namespace App\Tests\Service;

use App\Entity\Formation;
use App\Entity\Participation;
use App\Service\FormationManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour FormationManager
 */
class FormationManagerTest extends TestCase
{
    private FormationManager $formationManager;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        // Créer des mocks
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->formationManager = new FormationManager($this->entityManager);
    }

    /**
     * Test 1: Validation avec titre vide
     * Règle: Le titre est obligatoire et doit contenir au moins 3 caractères
     */
    public function testValidateFormationWithEmptyTitle(): void
    {
        $formation = new Formation();
        $formation->setFormateur('John Doe');
        $formation->setDateDebut(new DateTime('+1 day'));
        $formation->setType('Présentiel');
        $formation->setDuree(5);
        // Titre vide (null)

        $errors = $this->formationManager->validateFormation($formation);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('titre', strtolower($errors[0]));
    }

    /**
     * Test 2: Validation avec date dans le passé
     * Règle: La date de début ne peut pas être dans le passé
     */
    public function testValidateFormationWithPastDate(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation Symfony');
        $formation->setFormateur('John Doe');
        $formation->setDateDebut(new DateTime('-1 day'));
        $formation->setType('Présentiel');
        $formation->setDuree(5);

        $errors = $this->formationManager->validateFormation($formation);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('passé', strtolower($errors[0]));
    }

    /**
     * Test 3: Validation complète avec données valides
     * Règle: Toutes les données doivent être valides pour accepter la formation
     */
    public function testValidateFormationWithValidData(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation Symfony Avancé');
        $formation->setFormateur('Jane Smith');
        $formation->setDateDebut(new DateTime('+7 days'));
        $formation->setType('Hybride');
        $formation->setDuree(10);

        $errors = $this->formationManager->validateFormation($formation);

        $this->assertCount(0, $errors);
        $this->assertEmpty($errors);
    }

    /**
     * Test 4: Vérification de la capacité maximum (20 participants)
     * Règle: Une formation ne peut pas accepter plus de 20 participants
     */
    public function testCanAcceptParticipantsWithMaxLimit(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation Test');
        
        // Créer 20 participations avec statut "accepté"
        for ($i = 0; $i < 20; $i++) {
            $participation = new Participation();
            $participation->setStatut('accepté');
            $participation->setFormation($formation);
            $formation->addParticipation($participation);
        }

        // Doit retourner false car limite atteinte
        $canAccept = $this->formationManager->canAcceptParticipants($formation);
        $this->assertFalse($canAccept);

        // Vérifier le nombre de places disponibles
        $availablePlaces = $this->formationManager->getAvailablePlaces($formation);
        $this->assertEquals(0, $availablePlaces);
        $this->assertTrue($this->formationManager->isFull($formation));
    }

    /**
     * Test 5: Vérification places disponibles avec 5 participants
     * Règle: Si 5 participants, il reste 15 places
     */
    public function testGetAvailablePlacesWithFiveParticipants(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation Test');
        
        // Créer 5 participations
        for ($i = 0; $i < 5; $i++) {
            $participation = new Participation();
            $participation->setStatut('accepté');
            $participation->setFormation($formation);
            $formation->addParticipation($participation);
        }

        $availablePlaces = $this->formationManager->getAvailablePlaces($formation);
        $this->assertEquals(15, $availablePlaces);
        $this->assertTrue($this->formationManager->canAcceptParticipants($formation));
        $this->assertFalse($this->formationManager->isFull($formation));
    }

    /**
     * Test 6: Création de formation avec données invalides
     * Règle: Ne doit pas créer si validation échoue
     */
    public function testCreateFormationWithInvalidData(): void
    {
        $formation = new Formation();
        // Données invalides: pas de sujet, formateur trop court
        $formation->setSujet('AB'); // Trop court (< 3 caractères)
        $formation->setFormateur('Jo'); // Trop court (< 3 caractères)
        $formation->setDuree(-1); // Négatif

        // L'entityManager ne doit pas être appelé (pas de persist/flush)
        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->formationManager->createFormation($formation);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
        $this->assertNull($result['formation']);
    }

    /**
     * Test 7: Durée invalide (doit être > 0)
     * Règle: La durée doit être supérieure à 0
     */
    public function testValidateFormationWithInvalidDuration(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation PHP');
        $formation->setFormateur('John Doe');
        $formation->setDateDebut(new DateTime('+1 day'));
        $formation->setType('Présentiel');
        $formation->setDuree(0); // Durée invalide

        $errors = $this->formationManager->validateFormation($formation);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('durée', strtolower($errors[0]));
    }

    /**
     * Test 8: Vérification si une formation peut être modifiée
     * Règle: Une formation passée ne peut pas être modifiée
     */
    public function testCanBeModifiedWithPastFormation(): void
    {
        $formation = new Formation();
        $formation->setDateDebut(new DateTime('-5 days'));

        $canBeModified = $this->formationManager->canBeModified($formation);
        $this->assertFalse($canBeModified);
    }

    /**
     * Test 9: Type de formation invalide
     * Règle: Le type doit être En ligne, Présentiel ou Hybride
     */
    public function testValidateFormationWithInvalidType(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation Test');
        $formation->setFormateur('John Doe');
        $formation->setDateDebut(new DateTime('+1 day'));
        $formation->setType('Type Invalide');
        $formation->setDuree(5);

        $errors = $this->formationManager->validateFormation($formation);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('type', strtolower($errors[0]));
    }
}
