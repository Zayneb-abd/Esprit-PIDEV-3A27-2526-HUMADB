<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employ/feedback')]
class EmployeFeedbackController extends AbstractController
{
    #[Route('/', name: 'employ_feedback_index', methods: ['GET'])]
    public function index(FeedbackRepository $feedbackRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Fetch feedbacks submitted by this user (employee)
        // Since employe_id is an integer (not a full relation) in the database:
        $feedbacks = $feedbackRepository->findBy(
            ['employe_id' => $user->getId()],
            ['date_envoi' => 'DESC']
        );

        return $this->render('employ/feedback/index.html.twig', [
            'feedbacks' => $feedbacks,
        ]);
    }

    #[Route('/new', name: 'employ_feedback_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $feedback = new Feedback();
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $feedback->setEmployeId($user->getId());
            $feedback->setDateEnvoi(new \DateTime());
            $feedback->setStatus('nouveau');

            if ($feedback->isEstAnonyme() === null) {
                $feedback->setEstAnonyme(false);
            }

            $entityManager->persist($feedback);
            $entityManager->flush();

            $this->addFlash('success', 'Votre feedback a été envoyé avec succès !');

            return $this->redirectToRoute('employ_feedback_index');
        }

        return $this->render('employ/feedback/new.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'employ_feedback_show', methods: ['GET'])]
    public function show(Feedback $feedback): Response
    {
        $user = $this->getUser();
        // Security check: Employee can only view their own feedback
        if (!$user || $feedback->getEmployeId() !== $user->getId()) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à voir ce feedback.");
        }

        return $this->render('employ/feedback/show.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    #[Route('/{id}/delete', name: 'employ_feedback_delete', methods: ['POST'])]
    public function delete(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || $feedback->getEmployeId() !== $user->getId()) {
            throw $this->createAccessDeniedException("Action non autorisée.");
        }

        if ($this->isCsrfTokenValid('delete' . $feedback->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($feedback);
            $entityManager->flush();
            $this->addFlash('success', 'Feedback supprimé.');
        }

        return $this->redirectToRoute('employ_feedback_index');
    }
}
