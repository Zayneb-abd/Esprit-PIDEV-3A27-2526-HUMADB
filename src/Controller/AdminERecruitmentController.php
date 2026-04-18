<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Entretien;
use App\Entity\OffreEmploi;
use App\Entity\ResultatQuiz;
use App\Entity\User;
use App\Form\CandidatureType;
use App\Form\EntretienType;
use App\Repository\ResultatQuizRepository;
use App\Service\CandidateCvManager;
use App\Service\JitsiMeetService;
use App\Service\PdfCvPreviewService;
use App\Service\QuizGenerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminERecruitmentController extends AbstractController
{
    #[Route('/candidatures/new', name: 'admin_candidature_new')]
    public function newCandidature(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidature = new Candidature();
        $candidature->setDateCandidature(new \DateTime());
        $candidature->setDateStatut(new \DateTime());
        $candidature->setStatut('En attente');

        $form = $this->createForm(CandidatureType::class, $candidature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($candidature);
            $entityManager->flush();

            $this->addFlash('success', 'La candidature a ete creee.');

            return $this->redirectToRoute('admin_candidature_show', ['id' => $candidature->getId()]);
        }

        return $this->render('admin/candidature/new.html.twig', [
            'form' => $form->createView(),
            'candidature' => $candidature,
        ]);
    }

    #[Route('/candidatures/{id}', name: 'admin_candidature_show', requirements: ['id' => '\d+'])]
    public function showCandidature(Candidature $candidature, ResultatQuizRepository $resultatQuizRepository, CandidateCvManager $candidateCvManager, PdfCvPreviewService $pdfCvPreviewService): Response
    {
        $quizResult = null;
        foreach ($candidature->getOffreEmploi()?->getQuizs() ?? [] as $quiz) {
            $quizResult = $resultatQuizRepository->findOneBy([
                'user' => $candidature->getUser(),
                'quiz' => $quiz,
            ]);
            break;
        }

        $entretien = new Entretien();
        $entretien->setDateEntretien(new \DateTime('+2 days 10:00'));
        $entretien->setDureeMinutes(45);
        $entretien->setStatut('PLANIFIE');
        $entretienForm = $this->createForm(EntretienType::class, $entretien, [
            'action' => $this->generateUrl('admin_candidature_schedule_interview', ['id' => $candidature->getId()]),
            'method' => 'POST',
        ]);

        $cvAbsolutePath = $candidateCvManager->getApplicationCvAbsolutePath($candidature->getCv());
        $cvPreview = $pdfCvPreviewService->extractPreview($cvAbsolutePath);

        $entretiens = $candidature->getEntretiens()->toArray();
        usort($entretiens, static fn (Entretien $left, Entretien $right): int => ($right->getDateEntretien()?->getTimestamp() ?? 0) <=> ($left->getDateEntretien()?->getTimestamp() ?? 0));

        return $this->render('admin/candidature/show.html.twig', [
            'candidature' => $candidature,
            'cv_preview' => $cvPreview,
            'quiz_result' => $quizResult,
            'entretiens' => $entretiens,
            'entretien_form' => $entretienForm->createView(),
        ]);
    }

    #[Route('/candidatures/{id}/edit', name: 'admin_candidature_edit', requirements: ['id' => '\d+'])]
    public function editCandidature(Request $request, Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CandidatureType::class, $candidature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La candidature a ete mise a jour.');

            return $this->redirectToRoute('admin_candidature_show', ['id' => $candidature->getId()]);
        }

        return $this->render('admin/candidature/edit.html.twig', [
            'form' => $form->createView(),
            'candidature' => $candidature,
        ]);
    }

    #[Route('/candidatures/{id}/delete', name: 'admin_candidature_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteCandidature(Request $request, Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_candidature_'.$candidature->getId(), (string) $request->request->get('_token'))) {
            if ($candidature->getEntretiens()->count() > 0) {
                $this->addFlash('danger', 'Suppression impossible: la candidature est liee a des entretiens.');
            } else {
                $entityManager->remove($candidature);
                $entityManager->flush();
                $this->addFlash('success', 'La candidature a ete supprimee.');
            }
        }

        return $this->redirectToRoute('admin_inventory');
    }

    #[Route('/candidatures/{id}/entretiens/planifier', name: 'admin_candidature_schedule_interview', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function scheduleInterview(Candidature $candidature, Request $request, EntityManagerInterface $entityManager, JitsiMeetService $jitsiMeetService): Response
    {
        $entretien = new Entretien();
        $entretien->setCandidature($candidature);
        $entretien->setStatut('PLANIFIE');
        $entretien->setDureeMinutes(45);

        $form = $this->createForm(EntretienType::class, $entretien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $admin = $this->getUser();
            if ($admin instanceof User) {
                $entretien->setUser($admin);
            }

            if ($entretien->getStatut() === null) {
                $entretien->setStatut('PLANIFIE');
            }

            $meeting = $jitsiMeetService->createMeetingForInterview($candidature, $entretien);
            $entretien->setMeetLink($meeting['link']);
            $entretien->setCreatedAt(new \DateTime());

            if (in_array($candidature->getStatut(), ['En attente', 'En cours'], true)) {
                $candidature->setStatut('En cours');
                $candidature->setDateStatut(new \DateTime());
            }

            $entityManager->persist($entretien);
            $entityManager->flush();

            $this->addFlash('success', 'Entretien planifie avec succes.');
        } else {
            $this->addFlash('danger', 'Impossible de planifier l\'entretien. Verifiez les champs saisis.');
        }

        return $this->redirectToRoute('admin_candidature_show', ['id' => $candidature->getId()]);
    }

    #[Route('/api/entretiens/{id}/jitsi', name: 'admin_entretien_jitsi_api', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function jitsiApi(Entretien $entretien): JsonResponse
    {
        return $this->json([
            'id' => $entretien->getId(),
            'candidatureId' => $entretien->getCandidature()?->getId(),
            'adminId' => $entretien->getUser()?->getId(),
            'managerId' => $entretien->getManager()?->getId(),
            'participants' => [
                'candidat' => [
                    'id' => $entretien->getCandidature()?->getUser()?->getId(),
                    'nom' => trim((string) (($entretien->getCandidature()?->getUser()?->getPrenom() ?? '').' '.($entretien->getCandidature()?->getUser()?->getNom() ?? ''))),
                    'email' => $entretien->getCandidature()?->getUser()?->getEmail(),
                ],
                'admin' => [
                    'id' => $entretien->getUser()?->getId(),
                    'nom' => trim((string) (($entretien->getUser()?->getPrenom() ?? '').' '.($entretien->getUser()?->getNom() ?? ''))),
                    'email' => $entretien->getUser()?->getEmail(),
                ],
                'manager' => [
                    'id' => $entretien->getManager()?->getId(),
                    'nom' => trim((string) (($entretien->getManager()?->getPrenom() ?? '').' '.($entretien->getManager()?->getNom() ?? ''))),
                    'email' => $entretien->getManager()?->getEmail(),
                ],
            ],
            'scheduledAt' => $entretien->getDateEntretien()?->format(\DateTimeInterface::ATOM),
            'durationMinutes' => $entretien->getDureeMinutes(),
            'status' => $entretien->getStatut(),
            'meetLink' => $entretien->getMeetLink(),
        ]);
    }

    #[Route('/offres/{id}/quiz/generate', name: 'admin_offre_generate_quiz', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function generateQuizForOffer(OffreEmploi $offre, Request $request, QuizGenerationService $quizGenerationService): Response
    {
        if (!$this->isCsrfTokenValid('generate_quiz_'.$offre->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $replace = $request->request->getBoolean('replace');

        try {
            $quiz = $quizGenerationService->generateForOffer($offre, $replace);
            $message = $replace ? 'Le quiz a ete regenere avec succes.' : 'Le quiz a ete genere avec succes.';
            $this->addFlash('success', $message.' ('.$quiz->getQuestions()->count().' questions)');
        } catch (\RuntimeException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_offre_show', ['id' => $offre->getId()]);
    }
}
