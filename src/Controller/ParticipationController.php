<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Formation;
use App\Entity\Participation;
use App\Repository\FormationRepository;
use App\Repository\ParticipationRepository;
use App\Service\QrCodeService;
use App\Service\AIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employ/participation')]
class ParticipationController extends AbstractController
{
    #[Route('/formations', name: 'employ_participation_formations', methods: ['GET'])]
    public function listFormations(Request $request, FormationRepository $formationRepository): Response
    {
        $query = $request->query->get('q');
        $sortField = $request->query->get('sort');
        $sortOrder = $request->query->get('order', 'ASC');

        // On affiche les formations disponibles à l'inscription
        $formations = $formationRepository->searchAndSort($query, $sortField, $sortOrder);
        
        return $this->render('employ/participation/list_formations.html.twig', [
            'formations' => $formations,
            'query' => $query,
            'sort' => $sortField,
            'order' => $sortOrder,
        ]);
    }

    #[Route('/inscrire/{id}', name: 'employ_participation_inscrire', methods: ['POST'])]
    public function inscrire(Formation $formation, EntityManagerInterface $entityManager, ParticipationRepository $participationRepository): Response
    {
        // Get the current logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour vous inscrire à une formation.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'employé est déjà inscrit à cette formation
        $existingParticipation = $participationRepository->findOneBy([
            'user' => $user,
            'formation' => $formation
        ]);

        if ($existingParticipation) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette formation (Statut : ' . $existingParticipation->getStatut() . ').');
        } else {
            $participation = new Participation();
            if ($user instanceof User) {
                $participation->setUser($user);
            }
            $participation->setFormation($formation);
            $participation->setDateInscription(new \DateTime());
            $participation->setStatut('en attente');

            $entityManager->persist($participation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande d\'inscription à la formation "' . $formation->getSujet() . '" a été envoyée.');
        }

        return $this->redirectToRoute('employ_participation_formations');
    }

    #[Route('/mes-participations', name: 'employ_participation_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, ParticipationRepository $participationRepository): Response
    {
        // Get the current logged-in user
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour voir vos participations.');
            return $this->redirectToRoute('app_login');
        }

        $query = $request->query->get('q');
        $sortField = $request->query->get('sort');
        $sortOrder = $request->query->get('order', 'ASC');

        $userId = $user instanceof User ? $user->getId() : null;
        return $this->render('employ/participation/index.html.twig', [
            'participations' => $participationRepository->searchAndSort($query, $sortField, $sortOrder, $userId),
            'query' => $query,
            'sort' => $sortField,
            'order' => $sortOrder,
        ]);
    }

    #[Route('/{id}/detail', name: 'employ_participation_detail', methods: ['GET'])]
    public function detail(Participation $participation, QrCodeService $qrCodeService, AIService $aiService): Response
    {
        // Generate QR code data
        $qrCodeData = $qrCodeService->generateParticipationQrCodeData($participation);
        $qrCodeBase64 = $qrCodeService->generateQrCodeBase64($qrCodeData, 'Participation #' . $participation->getId());

        // Generate AI description and objectives for the formation
        $formationTitle = $participation->getFormation()->getSujet();
        $formationDescription = $participation->getFormation()->getLocalisation();
        
        // Always generate AI description based on title
        $aiDescription = $aiService->generateFormationDescription($formationTitle);
        
        // Generate objectives based on title and description
        $objectives = $aiService->generateFormationObjectives($formationTitle, $formationDescription);

        return $this->render('employ/participation/detail.html.twig', [
            'participation' => $participation,
            'qrCode' => $qrCodeBase64,
            'objectives' => $objectives,
            'aiDescription' => $aiDescription,
        ]);
    }

    #[Route('/{id}/qrcode/download', name: 'employ_participation_qrcode_download', methods: ['GET'])]
    public function downloadQrCode(Participation $participation, QrCodeService $qrCodeService): Response
    {
        // Generate QR code data
        $qrCodeData = $qrCodeService->generateParticipationQrCodeData($participation);
        $qrCodeBase64 = $qrCodeService->generateQrCodeBase64($qrCodeData, 'Participation #' . $participation->getId());

        // Convert base64 to binary
        $qrCodeBinary = base64_decode($qrCodeBase64);

        // Create response
        $response = new Response($qrCodeBinary);
        $response->headers->set('Content-Type', 'image/svg+xml');
        $response->headers->set('Content-Disposition', 'attachment; filename="participation_' . $participation->getId() . '_qrcode.svg"');

        return $response;
    }
}
