<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Publication;
use App\Entity\Commentaire;
use App\Repository\UserRepository;
use App\Repository\AbsenceRepository;
use App\Repository\CongeRepository;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\CommentValidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\MLPredictionService;

#[Route('/manager')]
#[IsGranted('ROLE_MANAGER')]
class ManagerController extends AbstractController
{
    private MLPredictionService $mlService;
    
    public function __construct(MLPredictionService $mlService)
    {
        $this->mlService = $mlService;
    }

    #[Route('/dashboard', name: 'manager_dashboard')]
    public function dashboard(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Appels ML pour le dashboard manager
        $prediction = $this->mlService->predictCongeProbability($user->getId());
        $suggestion = $this->mlService->suggestBestPeriod($user->getId(), 5);
        
        return $this->render('manager/dashboard.html.twig', [
            'prediction' => $prediction,
            'suggestion' => $suggestion,
            'manager' => $user
        ]);
    }
    #[Route('/equipe', name: 'manager_equipe')]
    public function equipe(UserRepository $userRepository): Response
    {
        /** @var User $manager */
        $manager = $this->getUser();
        
        // Get employees where manager_id = current manager's id
        $equipe = $userRepository->findBy(
            ['user' => $manager],
            ['nom' => 'ASC']
        );
        
        return $this->render('manager/equipe/index.html.twig', [
            'equipe' => $equipe,
            'manager' => $manager,
        ]);
    }

    #[Route('/conges', name: 'manager_conges')]
    public function conges(CongeRepository $congeRepository): Response
    {
        /** @var User $manager */
        $manager = $this->getUser();
        
        // Get team members
        $equipe = $manager->getUsers();
        $teamIds = array_map(fn($user) => $user->getId(), $equipe->toArray());
        
        // Get conges for team members
        $conges = $congeRepository->findBy(
            ['user' => $teamIds],
            ['date_demande' => 'DESC']
        );
        
        return $this->render('manager/conge/index.html.twig', [
            'conges' => $conges,
        ]);
    }

    #[Route('/absences', name: 'manager_absences')]
    public function absences(AbsenceRepository $absenceRepository): Response
    {
        /** @var User $manager */
        $manager = $this->getUser();
        
        // Get team members
        $equipe = $manager->getUsers();
        $teamIds = array_map(fn($user) => $user->getId(), $equipe->toArray());
        
        // Get absences for team members
        $absences = $absenceRepository->findBy(
            ['user' => $teamIds],
            ['date_debut' => 'DESC']
        );
        
        return $this->render('manager/absence/index.html.twig', [
            'absences' => $absences,
        ]);
    }

    #[Route('/feedbacks', name: 'manager_feedback_index')]
    public function feedbacks(): Response
    {
        return $this->render('manager/feedback/index.html.twig');
    }

    #[Route('/formations', name: 'manager_formation_index')]
    public function formations(): Response
    {
        return $this->render('manager/formation/index.html.twig');
    }

    #[Route('/participations', name: 'manager_participation_index')]
    public function participations(): Response
    {
        return $this->render('manager/participation/index.html.twig');
    }

    #[Route('/participations/formations', name: 'manager_participation_formations')]
    public function participationFormations(): Response
    {
        return $this->render('manager/participation/formations.html.twig');
    }

    #[Route('/publications', name: 'manager_publication_index')]
    public function publications(Request $request, PublicationRepository $publicationRepository, PaginatorInterface $paginator): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 6;

        $publications = $paginator->paginate(
            $publicationRepository->findBy([], ['date_publication' => 'DESC']),
            $page,
            $limit
        );

        return $this->render('manager/publication/index.html.twig', [
            'publications' => $publications,
        ]);
    }

    #[Route('/publications/{id}/comment', name: 'manager_publication_comment', methods: ['POST'])]
    public function addComment(Publication $publication, Request $request, EntityManagerInterface $em, CommentValidatorService $commentValidator): Response
    {
        /** @var string $contenu */
        $contenu = (string) $request->request->get('contenu');
        
        if (empty($contenu)) {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('manager_publication_index');
        }

        // Valider le commentaire avec BanBuilder
        $validation = $commentValidator->validateComment($contenu);

        if (!$validation['is_valid']) {
            // Si des mots interdits sont détectés
            if ($validation['is_censored']) {
                /** @var array<string> $badWords */
                $badWords = $validation['bad_words_found'];
                $this->addFlash('error', 'Commentaire rejeté : contient des mots inappropriés : ' . implode(', ', $badWords));
                return $this->redirectToRoute('manager_publication_index');
            }
        }

        $commentaire = new Commentaire();
        $commentaire->setContenu($contenu);
        $commentaire->setDate_commentaire(new \DateTime());
        $commentaire->setPublication($publication);
        /** @var User|null $user */
        $user = $this->getUser();
        $commentaire->setUser($user);

        $em->persist($commentaire);
        $em->flush();

        $this->addFlash('success', 'Commentaire ajouté avec succès.');
        return $this->redirectToRoute('manager_publication_index');
    }

    #[Route('/comment/{id}/delete', name: 'manager_comment_delete', methods: ['POST'])]
    public function deleteComment(Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        // Only allow user to delete their own comments
        if ($commentaire->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce commentaire.');
            return $this->redirectToRoute('manager_publication_index');
        }

        $em->remove($commentaire);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé avec succès.');
        return $this->redirectToRoute('manager_publication_index');
    }

    // Personal Conges Routes (same as employee)
    #[Route('/mes-conges', name: 'manager_mes_conges')]
    public function mesConges(CongeRepository $congeRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $conges = $congeRepository->findBy(
            ['user' => $user],
            ['date_demande' => 'DESC']
        );
        
        return $this->render('manager/conge/mes_conges.html.twig', [
            'conges' => $conges,
        ]);
    }

    #[Route('/mes-conges/new', name: 'manager_mes_conges_new', methods: ['GET', 'POST'])]
    public function newConge(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $errors = [];

        if ($request->isMethod('POST')) {
            $type = $request->request->get('type');
            $dateDebutStr = $request->request->get('date_debut');
            $dateFinStr = $request->request->get('date_fin');
            $motif = $request->request->get('motif', '');

            // Validation
            if (empty($type)) {
                $errors[] = 'Veuillez sélectionner un type de congé.';
            }
            if (empty($dateDebutStr)) {
                $errors[] = 'La date de début est obligatoire.';
            }
            if (empty($dateFinStr)) {
                $errors[] = 'La date de fin est obligatoire.';
            }

            if (empty($errors)) {
                $absence = new \App\Entity\Absence();
                $absence->setDateDebut(new \DateTime($dateDebutStr));
                $absence->setDateFin(new \DateTime($dateFinStr));
                $absence->setTypeAbsence($type);
                $absence->setStatut('en_attente');
                $absence->setMotif($motif);
                $absence->setUser($user);

                $conge = new \App\Entity\Conge();
                $conge->setDateDemande(new \DateTime());
                $conge->setUser($user);
                $conge->setAbsence($absence);

                $em->persist($absence);
                $em->persist($conge);
                $em->flush();

                $this->addFlash('success', 'Demande de congé créée avec succès.');
                return $this->redirectToRoute('manager_mes_conges');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('manager/conge/new.html.twig');
    }

    // Personal Absences - Manager can only VIEW, not ADD
    #[Route('/mes-absences', name: 'manager_mes_absences')]
    public function mesAbsences(AbsenceRepository $absenceRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $absences = $absenceRepository->findBy(
            ['user' => $user],
            ['date_debut' => 'DESC']
        );
        
        return $this->render('manager/absence/mes_absences.html.twig', [
            'absences' => $absences,
        ]);
    }

    // View employee profile (manager view)
    #[Route('/employe/{id}', name: 'manager_employe_profile')]
    public function employeProfile(int $id, UserRepository $userRepository, CongeRepository $congeRepository, AbsenceRepository $absenceRepository): Response
    {
        /** @var User $manager */
        $manager = $this->getUser();
        
        // Find the employee
        $employe = $userRepository->find($id);
        
        if (!$employe || $employe->getRole() !== 'EMPLOYE') {
            throw $this->createNotFoundException('Employé non trouvé.');
        }
        
        // Verify this employee belongs to the manager
        if ($employe->getUser() !== $manager) {
            $this->addFlash('error', 'Vous n\'avez pas accès à ce profil.');
            return $this->redirectToRoute('manager_equipe');
        }
        
        // Get employee's conges and absences
        $conges = $congeRepository->findBy(
            ['user' => $employe],
            ['date_demande' => 'DESC']
        );
        
        $absences = $absenceRepository->findBy(
            ['user' => $employe],
            ['date_debut' => 'DESC']
        );
        
        return $this->render('manager/equipe/employe_profile.html.twig', [
            'employe' => $employe,
            'conges' => $conges,
            'absences' => $absences,
        ]);
    }
}
