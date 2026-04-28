<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Form\AdminFeedbackType;
use App\Repository\FeedbackRepository;
use App\Repository\UserRepository;
use App\Service\FeedbackAutoResponseGenerator;
use App\Service\MeaningCloudSentimentService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/feedback')]
class AdminFeedbackController extends AbstractController
{
    #[Route('/', name: 'admin_feedback_index', methods: ['GET'])]
    public function index(Request $request, FeedbackRepository $feedbackRepository, PaginatorInterface $paginator): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $priorityFilter = trim((string) $request->query->get('priority', ''));
        $priorityFilter = $priorityFilter !== '' ? $priorityFilter : null;
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        $feedbacks = $paginator->paginate(
            $feedbackRepository->searchForAdmin($query, $priorityFilter),
            $page,
            $limit
        );

        $byStatus = $feedbackRepository->countByStatus();
        $byCategory = $feedbackRepository->countByCategory();
        $byPriority = $feedbackRepository->countByPriority();
        $timeline = $feedbackRepository->countByDayLastDays(7);
        $feedbackTotal = array_sum($byStatus);

        return $this->render('admin/feedback/index.html.twig', [
            'feedbacks' => $feedbacks,
            'query' => $query,
            'priority_filter' => $priorityFilter ?? '',
            'feedback_stats' => [
                'total' => $feedbackTotal,
                'by_status' => $byStatus,
                'by_category' => $byCategory,
                'by_priority' => $byPriority,
                'timeline_labels' => $timeline['labels'],
                'timeline_data' => $timeline['data'],
            ],
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_feedback_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Feedback $feedback,
        EntityManagerInterface $entityManager,
        MeaningCloudSentimentService $meaningCloudSentimentService
    ): Response
    {
        $form = $this->createForm(AdminFeedbackType::class, $feedback);
        $form->handleRequest($request);
        $sentiment = $meaningCloudSentimentService->analyze((string) $feedback->getContenu(), 'fr');

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Log the admin who handled the feedback
            $feedback->setUser($this->getUser());

            $entityManager->flush();
            $this->addFlash('success', 'Statut du feedback mis à jour.');

            return $this->redirectToRoute('admin_feedback_index');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Impossible de mettre a jour ce feedback. Verifiez les champs du formulaire.');
        }

        return $this->render('admin/feedback/edit.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
            'sentiment' => $sentiment,
        ]);
    }

    #[Route('/{id}/auto-response', name: 'admin_feedback_auto_response', methods: ['POST'])]
    public function generateAutoResponse(
        Request $request,
        Feedback $feedback,
        FeedbackAutoResponseGenerator $generator,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('feedback_ai_' . $feedback->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_feedback_edit', ['id' => $feedback->getId()]);
        }

        $text = $generator->generate($feedback);
        $feedback->setAutoResponse($text);
        $feedback->setAutoResponseGeneratedAt(new \DateTime());
        $feedback->setAutoResponseSentAt(null);
        $entityManager->flush();

        $this->addFlash('success', 'Réponse AI générée.');
        return $this->redirectToRoute('admin_feedback_edit', ['id' => $feedback->getId()]);
    }

    #[Route('/{id}/send-reply', name: 'admin_feedback_send_reply', methods: ['POST'])]
    public function sendReplyToEmployee(
        Feedback $feedback,
        Request $request,
        UserRepository $userRepository,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response {
        if ($this->isCsrfTokenValid('feedback_send_' . $feedback->getId(), (string) $request->request->get('_token')) === false) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_feedback_edit', ['id' => $feedback->getId()]);
        }

        $replyText = trim((string) $request->request->get('reply_text', ''));
        if ($replyText === '') {
            $this->addFlash('danger', 'La réponse ne peut pas être vide.');
            return $this->redirectToRoute('admin_feedback_edit', ['id' => $feedback->getId()]);
        }

        $employeeId = $feedback->getEmployeId();
        if (!$employeeId) {
            $this->addFlash('danger', 'Impossible d\'identifier l\'employé lié à ce feedback.');
            return $this->redirectToRoute('admin_feedback_edit', ['id' => $feedback->getId()]);
        }

        $employee = $userRepository->find($employeeId);
        if (!$employee || !$employee->getEmail()) {
            $this->addFlash('danger', 'Aucun email valide trouvé pour cet employé.');
            return $this->redirectToRoute('admin_feedback_edit', ['id' => $feedback->getId()]);
        }

        try {
            $email = (new Email())
                ->from('noreply@humadb.com')
                ->to($employee->getEmail())
                ->subject('Réponse à votre feedback #' . $feedback->getId())
                ->html(sprintf(
                    '<p>Bonjour %s,</p><p>Nous avons traité votre feedback.</p><p><strong>Votre message :</strong></p><blockquote>%s</blockquote><p><strong>Notre réponse :</strong></p><blockquote>%s</blockquote><p>Cordialement,<br>Équipe RH</p>',
                    htmlspecialchars((string) $employee->getPrenom()),
                    nl2br(htmlspecialchars((string) $feedback->getContenu())),
                    nl2br(htmlspecialchars($replyText))
                ));

            $mailer->send($email);
        } catch (\Throwable) {
            $this->addFlash('danger', 'Échec de l\'envoi de l\'email. Vérifiez la configuration MAILER_DSN.');
            return $this->redirectToRoute('admin_feedback_edit', ['id' => $feedback->getId()]);
        }

        $feedback->setAutoResponse($replyText);
        $feedback->setAutoResponseSentAt(new \DateTime());
        if ($feedback->getStatus() !== 'traite') {
            $feedback->setStatus('traite');
        }
        $feedback->setUser($this->getUser());
        $entityManager->flush();

        // Notification in-app pour l'employé
        $notificationService->sendToUser(
            $employee,
            'Réponse de l\'administration',
            'L\'administration a répondu à votre feedback concernant : ' . $feedback->getCategory(),
            '/employ/feedback' // Redirige vers la liste des feedbacks de l'employé où il verra le statut traité
        );

        $this->addFlash('success', 'Réponse envoyée à l\'employé avec succès.');
        return $this->redirectToRoute('admin_feedback_edit', ['id' => $feedback->getId()]);
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
