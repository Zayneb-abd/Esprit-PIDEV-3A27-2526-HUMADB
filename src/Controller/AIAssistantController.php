<?php

namespace App\Controller;

use App\Service\AIAssistantService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ai-assistant')]
class AIAssistantController extends AbstractController
{
    private AIAssistantService $aiService;
    private NotificationService $notificationService;
    private EntityManagerInterface $em;

    public function __construct(
        AIAssistantService $aiService,
        NotificationService $notificationService,
        EntityManagerInterface $em
    ) {
        $this->aiService = $aiService;
        $this->notificationService = $notificationService;
        $this->em = $em;
    }

    /**
     * Page principale de l'assistant IA
     */
    #[Route('/', name: 'ai_assistant_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        return $this->render('ai_assistant/index.html.twig', [
            'user' => $user,
            'role' => $user->getRole()
        ]);
    }

    /**
     * API endpoint pour parser une demande
     */
    #[Route('/parse', name: 'ai_assistant_parse', methods: ['POST'])]
    public function parseRequest(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        if (empty($message)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Message vide'
            ]);
        }

        $result = $this->aiService->parseRequest($message, $user);

        return new JsonResponse($this->normalizeParsedRequestForJson($result));
    }

    /**
     * API endpoint pour créer la demande
     */
    #[Route('/create', name: 'ai_assistant_create', methods: ['POST'])]
    public function createConge(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (empty($data['parsed_request'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Données manquantes'
            ]);
        }

        try {
            $conge = $this->aiService->createConge($this->normalizeParsedRequestPayload($data['parsed_request']), $user);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Impossible de créer la demande: '.$exception->getMessage(),
            ], 500);
        }

        if ($conge === null) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Impossible de créer la demande'
            ]);
        }

        // Envoyer notification
        $this->notificationService->notifyEmployeeRequestSubmitted($conge);
        if ($user->getUser()) {
            $this->notificationService->notifyManagerNewRequest($conge, $user->getUser());
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Demande de congé créée avec succès !',
            'conge_id' => $conge->getId(),
            'redirect_url' => $this->generateUrl(
                $user->getRole() === 'MANAGER' ? 'manager_conges' : 'employ_conges'
            )
        ]);
    }

    /**
     * Page de suggestion intelligente pour le manager
     */
    #[Route('/suggestions', name: 'ai_assistant_suggestions')]
    public function suggestions(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getRole() !== 'MANAGER') {
            return $this->redirectToRoute('ai_assistant_index');
        }

        // Analyser les patterns de l'équipe
        $suggestions = $this->generateTeamSuggestions($user);

        return $this->render('ai_assistant/suggestions.html.twig', [
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Génère des suggestions pour le manager
     */
    private function generateTeamSuggestions(User $manager): array
    {
        $suggestions = [];
        
        // Récupérer les membres de l'équipe
        $teamMembers = $this->em->getRepository(\App\Entity\User::class)
            ->findBy(['user' => $manager, 'role' => 'EMPLOYE']);

        // Analyser les périodes de conflit
        $conflictDates = [];
        $currentMonth = new \DateTime();
        
        for ($i = 0; $i < 30; $i++) {
            $date = clone $currentMonth;
            $date->modify("+{$i} days");
            
            $absents = 0;
            foreach ($teamMembers as $member) {
                $conges = $this->em->getRepository(\App\Entity\Conge::class)
                    ->findBy(['user' => $member]);
                foreach ($conges as $conge) {
                    $absence = $conge->getAbsence();
                    if ($absence && $absence->getStatut() === 'approuvé') {
                        if ($date >= $absence->getDateDebut() && $date <= $absence->getDateFin()) {
                            $absents++;
                            break;
                        }
                    }
                }
            }
            
            $percentage = count($teamMembers) > 0 ? ($absents / count($teamMembers)) * 100 : 0;
            
            if ($percentage >= 50) {
                $conflictDates[] = [
                    'date' => $date->format('d/m/Y'),
                    'absents' => $absents,
                    'total' => count($teamMembers),
                    'percentage' => round($percentage, 1)
                ];
            }
        }

        if (!empty($conflictDates)) {
            $suggestions[] = [
                'type' => 'warning',
                'title' => '⚠️ Risques de sous-effectif',
                'message' => count($conflictDates) . ' jours ont plus de 50% d\'absents ce mois-ci.',
                'details' => $conflictDates
            ];
        }

        // Suggérer des périodes calmes
        $calmPeriods = $this->findCalmPeriods($teamMembers);
        if (!empty($calmPeriods)) {
            $suggestions[] = [
                'type' => 'info',
                'title' => '✅ Périodes recommandées',
                'message' => 'Ces périodes ont moins de 30% d\'absents :',
                'details' => $calmPeriods
            ];
        }

        return $suggestions;
    }

    /**
     * Trouve les périodes calmes (moins de 30% d'absents)
     */
    private function findCalmPeriods(array $teamMembers): array
    {
        $calmPeriods = [];
        $currentMonth = new \DateTime();
        
        for ($week = 0; $week < 4; $week++) {
            $weekStart = clone $currentMonth;
            $weekStart->modify("+" . ($week * 7) . " days");
            $weekStart->modify('next monday');
            
            $maxAbsents = 0;
            for ($day = 0; $day < 5; $day++) { // Lundi-Vendredi
                $date = clone $weekStart;
                $date->modify("+{$day} days");
                
                $absents = 0;
                foreach ($teamMembers as $member) {
                    $conges = $this->em->getRepository(\App\Entity\Conge::class)
                        ->findBy(['user' => $member]);
                    foreach ($conges as $conge) {
                        $absence = $conge->getAbsence();
                        if ($absence && $absence->getStatut() === 'approuvé') {
                            if ($date >= $absence->getDateDebut() && $date <= $absence->getDateFin()) {
                                $absents++;
                                break;
                            }
                        }
                    }
                }
                
                $maxAbsents = max($maxAbsents, $absents);
            }
            
            $percentage = count($teamMembers) > 0 ? ($maxAbsents / count($teamMembers)) * 100 : 0;
            
            if ($percentage < 30) {
                $calmPeriods[] = [
                    'semaine' => 'Semaine du ' . $weekStart->format('d/m/Y'),
                    'max_absents' => $maxAbsents,
                    'percentage' => round($percentage, 1)
                ];
            }
        }

        return $calmPeriods;
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function normalizeParsedRequestForJson(array $result): array
    {
        if (isset($result['dates']) && is_array($result['dates'])) {
            $result['dates'] = $this->normalizeDatesForJson($result['dates']);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizeParsedRequestPayload(array $payload): array
    {
        if (isset($payload['dates']) && is_array($payload['dates'])) {
            $payload['dates'] = $this->normalizeDatesFromJson($payload['dates']);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $dates
     * @return array<string, mixed>
     */
    private function normalizeDatesForJson(array $dates): array
    {
        foreach (['debut', 'fin'] as $key) {
            if (($dates[$key] ?? null) instanceof \DateTimeInterface) {
                $dates[$key] = $dates[$key]->format(\DateTimeInterface::ATOM);
            }
        }

        return $dates;
    }

    /**
     * @param array<string, mixed> $dates
     * @return array<string, mixed>
     */
    private function normalizeDatesFromJson(array $dates): array
    {
        foreach (['debut', 'fin'] as $key) {
            if (($dates[$key] ?? null) instanceof \DateTimeInterface) {
                continue;
            }

            if (!isset($dates[$key]) || !is_string($dates[$key]) || trim($dates[$key]) === '') {
                continue;
            }

            try {
                $dates[$key] = new \DateTime($dates[$key]);
            } catch (\Throwable) {
                $dates[$key] = null;
            }
        }

        return $dates;
    }
}
