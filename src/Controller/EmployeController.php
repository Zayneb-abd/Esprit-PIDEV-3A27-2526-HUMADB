<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Absence;
use App\Entity\Conge;
use App\Repository\CongeRepository;
use App\WorkflowBundle\Repository\ApprovalHistoryRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\MLPredictionService;

#[Route('/employ')]
class EmployeController extends AbstractController
{
    private MLPredictionService $mlService;
    
    public function __construct(MLPredictionService $mlService)
    {
        $this->mlService = $mlService;
    }

    #[Route('/', name: 'employ_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        
        // Appels ML pour le dashboard
        $prediction = $this->mlService->predictCongeProbability($user->getId());
        $suggestion = $this->mlService->suggestBestPeriod($user->getId(), 5);
        
        return $this->render('employ/dashboard/index.html.twig', [
            'prediction' => $prediction,
            'suggestion' => $suggestion
        ]);
    }

    #[Route('/inventory', name: 'employ_inventory')]
    public function inventory(): Response
    {
        return $this->render('employ/inventory/index.html.twig');
    }

    #[Route('/product/create', name: 'employ_product_create')]
    public function createProduct(): Response
    {
        return $this->render('employ/product/create.html.twig');
    }

    #[Route('/reports', name: 'employ_reports')]
    public function reports(): Response
    {
        return $this->render('employ/reports/index.html.twig');
    }

    #[Route('/conges', name: 'employ_conges')]
    public function mesConges(CongeRepository $congeRepository, ApprovalHistoryRepository $historyRepo): Response
    {
        $user = $this->getUser();
        $conges = $congeRepository->findBy(['user' => $user], ['id' => 'DESC']);
        
        // Fetch comments for each conge
        $comments = [];
        foreach ($conges as $conge) {
            $history = $historyRepo->findOneByConge($conge);
            if ($history && $history->getComment()) {
                $comments[$conge->getId()] = $history->getComment();
            }
        }

        return $this->render('employ/conge/index.html.twig', [
            'conges' => $conges,
            'comments' => $comments
        ]);
    }

    #[Route('/conges/new', name: 'employ_conges_new', methods: ['GET', 'POST'])]
    public function newConge(Request $request, EntityManagerInterface $entityManager, NotificationService $notificationService): Response
    {
        if ($request->isMethod('POST')) {
            $user = $this->getUser();
            $dateDebutStr = $request->request->get('date_debut');
            $dateFinStr = $request->request->get('date_fin');
            $type = $request->request->get('type');
            $motif = $request->request->get('motif');

            $errors = [];
            $dateDebut = null;
            $dateFin = null;

            // Validation date début
            if (empty($dateDebutStr)) {
                $errors[] = 'La date de début est obligatoire.';
            } else {
                try {
                    $dateDebut = new \DateTime($dateDebutStr);
                    $today = new \DateTime('today');
                    $today->setTime(0, 0, 0);
                    $dateDebut->setTime(0, 0, 0);
                    
                    if ($dateDebut < $today) {
                        $errors[] = 'La date de début ne peut pas être dans le passé.';
                    }
                    
                    // Validation année max 2026
                    $yearDebut = (int)$dateDebut->format('Y');
                    if ($yearDebut > 2026) {
                        $errors[] = 'La date de début ne peut pas être en 2027 ou au-delà.';
                    }
                } catch (\Exception $e) {
                    $errors[] = 'La date de début n\'est pas valide.';
                }
            }

            // Validation date fin
            if (empty($dateFinStr)) {
                $errors[] = 'La date de fin est obligatoire.';
            } else {
                try {
                    $dateFin = new \DateTime($dateFinStr);
                    
                    // Validation année max 2026
                    $yearFin = (int)$dateFin->format('Y');
                    if ($yearFin > 2026) {
                        $errors[] = 'La date de fin ne peut pas être en 2027 ou au-delà.';
                    }
                    
                    if ($dateDebut && $dateFin <= $dateDebut) {
                        $errors[] = 'La date de fin doit être après la date de début.';
                    }
                } catch (\Exception $e) {
                    $errors[] = 'La date de fin n\'est pas valide.';
                }
            }

            // Validation durée maximale
            if ($dateDebut && $dateFin) {
                $diff = $dateDebut->diff($dateFin);
                $dureeJours = $diff->days;
                if ($dureeJours > 30) {
                    $errors[] = 'La durée maximale est de 30 jours.';
                }
                
                // Validation motif obligatoire pour maladie > 3 jours
                if ($type === 'MALADIE' && $dureeJours > 3 && empty($motif)) {
                    $errors[] = 'Le motif est obligatoire pour un congé maladie de plus de 3 jours.';
                }
            }

            // Validation type
            if (empty($type)) {
                $errors[] = 'Le type de congé est obligatoire.';
            }

            // Si erreurs, afficher
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('employ_conges_new');
            }

            // Créer l'absence
            $absence = new Absence();
            $absence->setUser($user);
            $absence->setDateDebut($dateDebut);
            $absence->setDateFin($dateFin);
            $absence->setTypeAbsence($type);
            $absence->setStatut('EN_ATTENTE');

            $entityManager->persist($absence);

            // Créer le congé lié
            $conge = new Conge();
            $conge->setUser($user);
            $conge->setAbsence($absence);
            $conge->setDateDemande(new \DateTime());

            $entityManager->persist($conge);
            $entityManager->flush();

            // Envoyer notifications
            $notificationService->notifyEmployeeRequestSubmitted($conge);
            if ($user->getUser()) {
                $notificationService->notifyManagerNewRequest($conge, $user->getUser());
            }

            $this->addFlash('success', 'Votre demande de congé a été soumise avec succès.');
            return $this->redirectToRoute('employ_conges');
        }

        return $this->render('employ/conge/new.html.twig');
    }

    #[Route('/conges/{id}/show', name: 'employ_conges_show')]
    public function showConge(int $id): Response
    {
        return $this->render('employ/conge/show.html.twig', ['id' => $id]);
    }

    #[Route('/conges/{id}/edit', name: 'employ_conges_edit', methods: ['GET', 'POST'])]
    public function editConge(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $conge = $em->getRepository(Conge::class)->find($id);

        if (!$conge) {
            $this->addFlash('error', 'Demande de congé non trouvée.');
            return $this->redirectToRoute('employ_conges');
        }

        // Vérifier que l'utilisateur est bien le propriétaire
        $currentUser = $this->getUser();
        if ($conge->getUser() !== $currentUser) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette demande.');
            return $this->redirectToRoute('employ_conges');
        }

        // Récupérer l'absence liée
        $absence = $conge->getAbsence();

        // Traitement du formulaire (POST)
        if ($request->isMethod('POST')) {
            $dateDebut = $request->request->get('date_debut');
            $dateFin = $request->request->get('date_fin');
            $type = $request->request->get('type');
            $motif = $request->request->get('motif');

            // Validation des données
            $errors = [];

            if (empty($dateDebut)) {
                $errors[] = 'La date de début est obligatoire.';
            }
            if (empty($dateFin)) {
                $errors[] = 'La date de fin est obligatoire.';
            }
            if (empty($type)) {
                $errors[] = 'Le type de congé est obligatoire.';
            }

            // Si pas d'erreurs, mettre à jour
            if (empty($errors)) {
                if ($absence) {
                    $absence->setDate_debut(new \DateTime($dateDebut));
                    $absence->setDate_fin(new \DateTime($dateFin));
                    $absence->setType_absence($type);
                }

                // Mettre à jour le motif dans le congé
                $conge->setCommentaire_validation($motif);

                $em->flush();

                $this->addFlash('success', 'Demande de congé modifiée avec succès.');
                return $this->redirectToRoute('employ_conges');
            }

            // Afficher les erreurs
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('employ/conge/edit.html.twig', [
            'id' => $id,
            'conge' => $conge,
            'absence' => $absence,
        ]);
    }

    #[Route('/conges/{id}/delete', name: 'employ_conges_delete', methods: ['POST'])]
    public function deleteConge(int $id, EntityManagerInterface $em): Response
    {
        $conge = $em->getRepository(Conge::class)->find($id);

        if (!$conge) {
            $this->addFlash('error', 'Demande de congé non trouvée.');
            return $this->redirectToRoute('employ_conges');
        }

        // Vérifier que l'utilisateur est bien le propriétaire de la demande
        $currentUser = $this->getUser();
        if ($conge->getUser() !== $currentUser) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cette demande.');
            return $this->redirectToRoute('employ_conges');
        }

        // Supprimer d'abord l'absence liée si elle existe
        $absence = $conge->getAbsence();
        if ($absence) {
            $em->remove($absence);
        }

        $em->remove($conge);
        $em->flush();

        $this->addFlash('success', 'Demande de congé supprimée avec succès.');
        return $this->redirectToRoute('employ_conges');
    }

    #[Route('/absences', name: 'employ_absences')]
    public function mesAbsences(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Récupérer les absences de l'employé connecté
        $qb = $em->createQueryBuilder();
        $qb->select('a')
            ->from(Absence::class, 'a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.date_debut', 'DESC');

        $absences = $qb->getQuery()->getResult();

        return $this->render('employ/absence/index.html.twig', [
            'absences' => $absences,
        ]);
    }

    #[Route('/docs', name: 'employ_docs')]
    public function docs(): Response
    {
        return $this->render('employ/docs/index.html.twig');
    }
}
