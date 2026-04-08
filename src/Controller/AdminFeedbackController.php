<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Form\AdminFeedbackType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/feedback')]
class AdminFeedbackController extends AbstractController
{
    #[Route('/', name: 'admin_feedback_index', methods: ['GET'])]
    public function index(FeedbackRepository $feedbackRepository): Response
    {
        $feedbacks = $feedbackRepository->findBy([], ['date_envoi' => 'DESC']);

        return $this->render('admin/feedback/index.html.twig', [
            'feedbacks' => $feedbacks,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_feedback_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdminFeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Log the admin who handled the feedback
            $feedback->setUser($this->getUser());

            $entityManager->flush();
            $this->addFlash('success', 'Statut du feedback mis à jour.');

            return $this->redirectToRoute('admin_feedback_index');
        }

        return $this->render('admin/feedback/edit.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_feedback_delete', methods: ['POST'])]
    public function delete(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $feedback->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($feedback);
            $entityManager->flush();
            $this->addFlash('success', 'Feedback supprimé.');
        }

        return $this->redirectToRoute('admin_feedback_index');
    }
}
