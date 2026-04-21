<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Entretien;
use App\Entity\OffreEmploi;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\ReponseQcm;
use App\Entity\ResultatQuiz;
use App\Entity\User;
use App\Form\CandidatureType;
use App\Form\EntretienType;
use App\Repository\ResultatQuizRepository;
use App\Service\CandidateCvManager;
use App\Service\CvMatchingService;
use App\Service\ExternalAiRecruitmentAnalyzer;
use App\Service\FacebookJobPublisher;
use App\Service\JitsiMeetService;
use App\Service\PdfCvPreviewService;
use App\Service\PublicProfileSourcingService;
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
    #[Route('/offres/{id}', name: 'admin_offre_show', requirements: ['id' => '\d+'])]
    public function showOffre(OffreEmploi $offre, CvMatchingService $cvMatchingService, ExternalAiRecruitmentAnalyzer $externalAiRecruitmentAnalyzer, FacebookJobPublisher $facebookJobPublisher): Response
    {
        return $this->render('admin/offre_emploi/show.html.twig', [
            'offre' => $offre,
            'cv_rankings' => $cvMatchingService->rankForOffer($offre, $offre->getCandidatures()),
            'external_ai_configured' => $externalAiRecruitmentAnalyzer->isConfigured(),
            'facebook_publish_configured' => $facebookJobPublisher->isConfigured(),
        ]);
    }

    #[Route('/offres/{id}/sourcing/public-urls', name: 'admin_offre_public_sourcing', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function sourcePublicProfiles(OffreEmploi $offre, Request $request, PublicProfileSourcingService $publicProfileSourcingService, ExternalAiRecruitmentAnalyzer $externalAiRecruitmentAnalyzer): Response
    {
        $results = [];
        $rawUrls = '';
        $searchMode = 'auto';

        if ($request->isMethod('POST')) {
            $searchMode = (string) $request->request->get('search_mode', 'auto');
            $rawUrls = trim((string) $request->request->get('urls', ''));
            $urls = array_values(array_filter(array_map('trim', preg_split('/\R+/', $rawUrls) ?: [])));

            if ($searchMode === 'manual' && $urls !== []) {
                $results = $publicProfileSourcingService->sourceForOffer($offre, $urls);
                $this->addFlash('success', sprintf('%d profil(s) public(s) analyses.', count($results)));
            } else {
                try {
                    $results = $publicProfileSourcingService->searchForOffer($offre);

                    if ($results === []) {
                        $this->addFlash('warning', 'Aucun profil public pertinent n a ete trouve a partir de la description de l offre.');
                    } else {
                        $fallbackCount = count(array_filter(
                            $results,
                            static fn (array $item): bool => ($item['status'] ?? '') === 'scraping_fallback'
                        ));

                        if ($fallbackCount > 0) {
                            $this->addFlash('success', sprintf('%d resultat(s) trouves par le bot Goutte. %d proviennent d un fallback quand le profil complet n etait pas disponible.', count($results), $fallbackCount));
                        } else {
                            $this->addFlash('success', sprintf('%d profil(s) public(s) trouves et analyses a partir de l offre par le bot Goutte.', count($results)));
                        }
                    }
                } catch (\Throwable $exception) {
                    $this->addFlash('danger', 'Recherche automatique impossible: '.$exception->getMessage());
                }
            }
        }

        return $this->render('admin/offre_emploi/public_sourcing.html.twig', [
            'offre' => $offre,
            'results' => $results,
            'raw_urls' => $rawUrls,
            'search_mode' => $searchMode,
            'external_ai_configured' => $externalAiRecruitmentAnalyzer->isConfigured(),
        ]);
    }

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
            'cv_public_path' => $candidateCvManager->getApplicationCvPublicPath($candidature->getCv()),
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

    #[Route('/offres/{id}/publish/facebook', name: 'admin_offre_publish_facebook', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publishOfferOnFacebook(OffreEmploi $offre, Request $request, FacebookJobPublisher $facebookJobPublisher): Response
    {
        if (!$this->isCsrfTokenValid('publish_facebook_'.$offre->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if (!$facebookJobPublisher->isConfigured()) {
            $this->addFlash('warning', 'La publication Facebook n est pas configuree.');

            return $this->redirectToRoute('admin_offre_show', ['id' => $offre->getId()]);
        }

        try {
            $facebookJobPublisher->publishOffer($offre);
            $this->addFlash('success', 'L offre a ete publiee sur Facebook.');
        } catch (\Throwable $exception) {
            $this->addFlash('danger', 'Impossible de publier l offre sur Facebook: '.$exception->getMessage());
        }

        return $this->redirectToRoute('admin_offre_show', ['id' => $offre->getId()]);
    }

    #[Route('/offres/{id}/quiz/new', name: 'admin_offre_quiz_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function createManualQuiz(OffreEmploi $offre, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $titre = trim((string) $request->request->get('titre', ''));
            $dureeMinutes = max(1, (int) $request->request->get('duree_minutes', 12));
            $seuilPourcentage = max(0, min(100, (int) $request->request->get('seuil_pourcentage', 70)));
            $questionsData = $request->request->all('questions');

            if ($titre === '') {
                $this->addFlash('danger', 'Le titre du quiz est obligatoire.');

                return $this->render('admin/offre_emploi/quiz_new.html.twig', [
                    'offre' => $offre,
                ]);
            }

            if (!is_array($questionsData) || $questionsData === []) {
                $this->addFlash('danger', 'Ajoutez au moins une question au quiz.');

                return $this->render('admin/offre_emploi/quiz_new.html.twig', [
                    'offre' => $offre,
                ]);
            }

            $existingQuiz = $offre->getQuizs()->first();
            if ($existingQuiz instanceof Quiz) {
                if ($existingQuiz->getResultatQuizs()->count() > 0) {
                    $this->addFlash('danger', 'Impossible de remplacer le quiz: des candidats l ont deja passe.');

                    return $this->redirectToRoute('admin_offre_show', ['id' => $offre->getId()]);
                }

                foreach ($existingQuiz->getQuestions()->toArray() as $existingQuestion) {
                    foreach ($existingQuestion->getReponseQcms()->toArray() as $existingAnswer) {
                        $entityManager->remove($existingAnswer);
                    }

                    $entityManager->remove($existingQuestion);
                }

                $entityManager->remove($existingQuiz);
                $entityManager->flush();
            }

            $quiz = new Quiz();
            $quiz->setOffreEmploi($offre);
            $quiz->setTitre($titre);
            $quiz->setDureeMinutes($dureeMinutes);
            $quiz->setSeuilPourcentage($seuilPourcentage);
            $entityManager->persist($quiz);

            $validQuestions = 0;

            foreach ($questionsData as $questionData) {
                $questionText = trim((string) ($questionData['question_text'] ?? ''));
                if ($questionText === '') {
                    continue;
                }

                $question = new Question();
                $question->setQuiz($quiz);
                $question->setQuestionText($questionText);
                $question->setType('QCM');
                $question->setPoints(max(1, (int) ($questionData['points'] ?? 1)));
                $entityManager->persist($question);

                $correctAnswers = 0;
                foreach (($questionData['answers'] ?? []) as $answerData) {
                    $text = trim((string) ($answerData['texte'] ?? ''));
                    if ($text === '') {
                        continue;
                    }

                    $answer = new ReponseQcm();
                    $answer->setQuestion($question);
                    $answer->setTexte($text);
                    $answer->setCorrecte(isset($answerData['correcte']) && (string) $answerData['correcte'] === '1');
                    if ($answer->isCorrecte()) {
                        ++$correctAnswers;
                    }
                    $entityManager->persist($answer);
                }

                if ($correctAnswers === 0) {
                    $this->addFlash('danger', sprintf('Chaque question doit avoir au moins une bonne reponse. Probleme sur: "%s".', $questionText));

                    return $this->render('admin/offre_emploi/quiz_new.html.twig', [
                        'offre' => $offre,
                    ]);
                }

                ++$validQuestions;
            }

            if ($validQuestions === 0) {
                $this->addFlash('danger', 'Le quiz doit contenir au moins une question valide.');

                return $this->render('admin/offre_emploi/quiz_new.html.twig', [
                    'offre' => $offre,
                ]);
            }

            $entityManager->flush();

            $this->addFlash('success', sprintf('Quiz cree avec succes (%d questions).', $validQuestions));

            return $this->redirectToRoute('admin_offre_show', ['id' => $offre->getId()]);
        }

        return $this->render('admin/offre_emploi/quiz_new.html.twig', [
            'offre' => $offre,
        ]);
    }
}
