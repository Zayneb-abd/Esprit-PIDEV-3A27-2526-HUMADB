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
    public function index(Request $request, FeedbackRepository $feedbackRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $query = trim((string) $request->query->get('q', ''));
        $feedbacks = $feedbackRepository->searchForEmployee($user->getId(), $query);

        return $this->render('employ/feedback/index.html.twig', [
            'feedbacks' => $feedbacks,
            'query' => $query,
        ]);
    }

    #[Route('/new', name: 'employ_feedback_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        \App\Service\FeedbackPriorityAnalyzer $priorityAnalyzer,
        \App\Service\NotificationService $notificationService
    ): Response {
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

            // Analyse de priorité par l'IA (ou fallback) !
            $priority = $priorityAnalyzer->analyze($feedback);
            $feedback->setPriority($priority);

            if ($feedback->isEstAnonyme() === null) {
                $feedback->setEstAnonyme(false);
            }

            $entityManager->persist($feedback);
            $entityManager->flush();

            // Notification aux Admins
            $notificationService->sendToAdmins(
                'Nouveau Feedback (' . $priority . ')',
                'Un employé vient de soumettre un nouveau feedback dans la catégorie : ' . $feedback->getCategory(),
                '/admin/feedback/' . $feedback->getId() . '/edit' // C'est un lien vers le feedback
            );

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

    #[Route('/{id}/edit', name: 'employ_feedback_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || $feedback->getEmployeId() !== $user->getId()) {
            throw $this->createAccessDeniedException("Action non autorisée.");
        }

        // Employee can only edit feedback while it's still new.
        if ($feedback->getStatus() !== 'nouveau') {
            $this->addFlash('warning', 'Ce feedback ne peut plus etre modifie.');
            return $this->redirectToRoute('employ_feedback_show', ['id' => $feedback->getId()]);
        }

        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Feedback mis a jour avec succes.');
            return $this->redirectToRoute('employ_feedback_show', ['id' => $feedback->getId()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Veuillez corriger les erreurs du formulaire.');
        }

        return $this->render('employ/feedback/edit.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
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
