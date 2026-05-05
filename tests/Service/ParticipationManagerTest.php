<?php

namespace App\Tests\Service;

use App\Entity\Formation;
use App\Entity\Participation;
use App\Entity\User;
use App\Repository\ParticipationRepository;
use App\Service\FormationManager;
use App\Service\ParticipationManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ParticipationManager
 */
class ParticipationManagerTest extends TestCase
{
    private ParticipationManager $participationManager;
    private ParticipationRepository $participationRepository;
    private FormationManager $formationManager;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        // Utiliser getMockBuilder pour contourner les constructeurs
        $this->participationRepository = $this->getMockBuilder(ParticipationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->formationManager = $this->getMockBuilder(FormationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->participationManager = new ParticipationManager(
            $this->participationRepository,
            $this->formationManager,
            $this->entityManager
        );
    }

    /**
     * Test 1: Validation avec statut invalide
     * Règle: Le statut est obligatoire et doit être valide
     */
    public function testValidateParticipationWithInvalidStatut(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation Test');

        $participation = new Participation();
        $participation->setFormation($formation);
        $participation->setDateInscription(new DateTime());
        $participation->setStatut('statut invalide'); // Statut invalide

        // Configurer le mock pour accepter les participants
        $this->formationManager->expects($this->any())
            ->method('canAcceptParticipants')
            ->willReturn(true);

        $errors = $this->participationManager->validateParticipation($participation);

        $this->assertGreaterThanOrEqual(1, count($errors));
        $this->assertStringContainsString('statut', strtolower(implode(' ', $errors)));
    }

    /**
     * Test 2: Validation avec formation obligatoire manquante
     * Règle: La formation est obligatoire
     */
    public function testValidateParticipationWithMissingFormation(): void
    {
        $participation = new Participation();
        $participation->setStatut('en attente');
        $participation->setDateInscription(new DateTime());
        // Formation null

        $errors = $this->participationManager->validateParticipation($participation);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('formation', strtolower($errors[0]));
    }

    /**
     * Test 3: Validation avec formation complète
     * Règle: Ne peut pas s'inscrire si formation complète (20 participants)
     */
    public function testValidateParticipationWithFullFormation(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation Complète');

        $participation = new Participation();
        $participation->setStatut('en attente');
        $participation->setFormation($formation);
        $participation->setDateInscription(new DateTime());

        // Simuler que la formation ne peut plus accepter de participants
        $this->formationManager->expects($this->once())
            ->method('canAcceptParticipants')
            ->with($formation)
            ->willReturn(false);

        $errors = $this->participationManager->validateParticipation($participation);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('complète', strtolower($errors[0]));
    }

    /**
     * Test 4: Validation avec données complètes valides
     * Règle: Toutes les données doivent être valides
     */
    public function testValidateParticipationWithValidData(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation PHP');

        $participation = new Participation();
        $participation->setStatut('en attente');
        $participation->setFormation($formation);
        $participation->setDateInscription(new DateTime());

        // Simuler que la formation peut accepter des participants
        $this->formationManager->expects($this->once())
            ->method('canAcceptParticipants')
            ->with($formation)
            ->willReturn(true);

        $errors = $this->participationManager->validateParticipation($participation);

        $this->assertCount(0, $errors);
        $this->assertEmpty($errors);
    }

    /**
     * Test 5: Création de participation réussie
     * Règle: Doit créer si validation passe
     */
    public function testCreateParticipationSuccessfully(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation Symfony');

        $participation = new Participation();
        $participation->setStatut('en attente');
        $participation->setFormation($formation);
        $participation->setDateInscription(new DateTime()); // Date d'inscription requise

        // Configurer le mock pour accepter plusieurs appels
        $this->formationManager->expects($this->any())
            ->method('canAcceptParticipants')
            ->willReturn(true);

        // L'entityManager doit être appelé pour persist et flush
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($participation);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->participationManager->createParticipation($participation);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);
        $this->assertNotNull($result['participation']);
        $this->assertInstanceOf(Participation::class, $result['participation']);
    }

    /**
     * Test 6: Création de participation échouée (validation)
     * Règle: Ne doit pas créer si validation échoue
     */
    public function testCreateParticipationWithInvalidData(): void
    {
        $participation = new Participation();
        // Données invalides: pas de formation, pas de statut

        // L'entityManager ne doit pas être appelé (pas de persist/flush)
        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->participationManager->createParticipation($participation);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
        $this->assertNull($result['participation']);
    }

    /**
     * Test 7: Vérification utilisateur déjà inscrit
     * Règle: Ne peut pas s'inscrire 2 fois à la même formation
     */
    public function testIsUserAlreadyRegistered(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $formation = new Formation();
        $formation->setSujet('Formation Test');

        $existingParticipation = new Participation();
        $existingParticipation->setUser($user);
        $existingParticipation->setFormation($formation);
        $existingParticipation->setStatut('en attente');

        // Simuler que le repository trouve une participation existante
        $this->participationRepository->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user, 'formation' => $formation])
            ->willReturn([$existingParticipation]);

        $isRegistered = $this->participationManager->isUserAlreadyRegistered($user, $formation);

        $this->assertTrue($isRegistered);
    }

    /**
     * Test 8: Changement de statut vers "accepté" avec formation complète
     * Règle: Ne peut pas accepter si formation complète
     */
    public function testChangeStatusToAcceptedWithFullFormation(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation Complète');

        $participation = new Participation();
        $participation->setFormation($formation);
        $participation->setStatut('en attente');

        $this->formationManager->expects($this->once())
            ->method('canAcceptParticipants')
            ->with($formation)
            ->willReturn(false);

        $result = $this->participationManager->changeStatus($participation, 'accepté');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('complète', strtolower($result['error']));
    }

    /**
     * Test 9: Date d'inscription dans le futur (invalide)
     * Règle: La date d'inscription ne peut pas être dans le futur
     */
    public function testValidateParticipationWithFutureDate(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation Test');

        $participation = new Participation();
        $participation->setStatut('en attente');
        $participation->setFormation($formation);
        $participation->setDateInscription(new DateTime('+1 day'));

        $this->formationManager->expects($this->once())
            ->method('canAcceptParticipants')
            ->willReturn(true);

        $errors = $this->participationManager->validateParticipation($participation);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('futur', strtolower($errors[0]));
    }

    /**
     * Test 10: Annulation de participation impossible (formation commencée)
     * Règle: Ne peut pas annuler si formation déjà commencée
     */
    public function testCanBeCancelledWithStartedFormation(): void
    {
        $formation = new Formation();
        $formation->setSujet('Formation Passée');
        $formation->setDateDebut(new DateTime('-2 days'));

        $participation = new Participation();
        $participation->setFormation($formation);

        $canCancel = $this->participationManager->canBeCancelled($participation);

        $this->assertFalse($canCancel);
    }
}
