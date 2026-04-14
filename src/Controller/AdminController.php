<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Commentaire;
use App\Entity\Log;
use App\Entity\OffreEmploi;
use App\Entity\Publication;
use App\Entity\User;
use App\Form\AdminUserType;
use App\Form\CandidatureType;
use App\Form\CommentaireType;
use App\Form\OffreEmploiType;
use App\Form\PublicationType;
use App\Repository\CandidatureRepository;
use App\Repository\LogRepository;
use App\Repository\OffreEmploiRepository;
use App\Repository\PublicationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    // ─────────────── DASHBOARD ───────────────

    #[Route('/', name: 'admin_dashboard')]
    #[Route('/dashboard', name: 'admin_dashboard_alt')]
    public function dashboard(UserRepository $userRepository): Response
    {
        $totalUsers = $userRepository->count([]);
        $countByRole = $userRepository->countByRole();
        $recentUsers = $userRepository->findRecentUsers(5);

        return $this->render('admin/dashboard.html.twig', [
            'total_users'  => $totalUsers,
            'count_by_role' => $countByRole,
            'recent_users' => $recentUsers,
        ]);
    }

    // ─────────────── USER CRUD ───────────────

    #[Route('/users', name: 'admin_users', methods: ['GET'])]
    public function listUsers(Request $request, UserRepository $userRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $role   = trim((string) $request->query->get('role', ''));
        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = 10;

        $users = $userRepository->searchPaginated($search, $role, $page, $limit);
        $total = $userRepository->countSearch($search, $role);
        $pages = (int) ceil($total / $limit);

        return $this->render('admin/users.html.twig', [
            'users'  => $users,
            'search' => $search,
            'role'   => $role,
            'page'   => $page,
            'pages'  => $pages,
            'total'  => $total,
        ]);
    }

    #[Route('/users/add', name: 'admin_user_add', methods: ['GET', 'POST'])]
    public function addUser(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,
    ): Response {
        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existing) {
                $this->addFlash('danger', 'Cet email est déjà utilisé.');
                return $this->render('admin/add_user.html.twig', ['form' => $form->createView()]);
            }

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setMdp($passwordHasher->hashPassword($user, $plainPassword));
            }

            $faceImageFile = $form->get('faceImageFile')->getData();
            if ($faceImageFile) {
                $safeFilename = $slugger->slug(pathinfo($faceImageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename  = $safeFilename . '-' . uniqid() . '.' . $faceImageFile->guessExtension();
                try {
                    $faceImageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/faces', $newFilename);
                    $user->setFaceImage($newFilename);
                } catch (FileException) {
                    $this->addFlash('warning', 'Erreur lors du téléchargement de l\'image.');
                }
            }

            $user->setReputationScore(0);
            $em->persist($user);
            $em->flush();

            $log = new Log();
            $log->setUser($this->getUser() instanceof User ? $this->getUser() : null);
            $log->setAction('user_created (admin: ' . $user->getEmail() . ')');
            $em->persist($log);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/add_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/{id}/edit', name: 'admin_user_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editUser(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,
    ): Response {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $form = $this->createForm(AdminUserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setMdp($passwordHasher->hashPassword($user, $plainPassword));
            }

            $faceImageFile = $form->get('faceImageFile')->getData();
            if ($faceImageFile) {
                $safeFilename = $slugger->slug(pathinfo($faceImageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename  = $safeFilename . '-' . uniqid() . '.' . $faceImageFile->guessExtension();
                try {
                    $faceImageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/faces', $newFilename);
                    $user->setFaceImage($newFilename);
                } catch (FileException) {
                    $this->addFlash('warning', 'Erreur lors du téléchargement de l\'image.');
                }
            }

            $em->flush();

            $log = new Log();
            $log->setUser($this->getUser() instanceof User ? $this->getUser() : null);
            $log->setAction('user_updated (admin edited: ' . $user->getEmail() . ')');
            $em->persist($log);
            $em->flush();

            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/edit_user.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteUser(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
    ): Response {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if ($this->isCsrfTokenValid('delete_user_' . $id, (string) $request->request->get('_token'))) {
            $email = $user->getEmail();
            $em->remove($user);
            $em->flush();

            $log = new Log();
            $log->setUser(null);
            $log->setAction('user_deleted (admin deleted: ' . $email . ')');
            $em->persist($log);
            $em->flush();

            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/toggle-status', name: 'admin_user_toggle_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleUserStatus(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
    ): Response {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if ($this->isCsrfTokenValid('toggle_status_user_' . $id, (string) $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $em->flush();

            $this->addFlash('success', $user->isActive()
                ? 'Compte utilisateur active.'
                : 'Compte utilisateur desactive.');
        }

        return $this->redirectToRoute('admin_users', [
            'q' => (string) $request->query->get('q', ''),
            'role' => (string) $request->query->get('role', ''),
            'page' => (int) $request->query->get('page', 1),
        ]);
    }

    #[Route('/users/export', name: 'admin_users_export', methods: ['GET'])]
    public function exportUsers(Request $request, UserRepository $userRepository): StreamedResponse
    {
        $search = trim((string) $request->query->get('q', ''));
        $role = trim((string) $request->query->get('role', ''));
        $users = $userRepository->findForExport($search, $role);

        $response = new StreamedResponse(function () use ($users): void {
            $handle = fopen('php://output', 'w');
            if (!$handle) {
                return;
            }

            fputcsv($handle, ['id', 'nom', 'prenom', 'email', 'role', 'is_active', 'reputation_score', 'date_naissance']);
            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->getId(),
                    $user->getNom(),
                    $user->getPrenom(),
                    $user->getEmail(),
                    $user->getRole(),
                    $user->isActive() ? '1' : '0',
                    $user->getReputationScore(),
                    $user->getDateNaissance() ? $user->getDateNaissance()->format('Y-m-d') : '',
                ]);
            }
            fclose($handle);
        });

        $filename = 'users_export_' . (new \DateTime())->format('Ymd_His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    // ─────────────── LOGS ───────────────

    #[Route('/logs', name: 'admin_logs', methods: ['GET'])]
    public function logs(Request $request, LogRepository $logRepository): Response
    {
        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $logs  = $logRepository->findAllOrderedByDate($limit, ($page - 1) * $limit);

        return $this->render('admin/logs.html.twig', [
            'logs' => $logs,
            'page' => $page,
        ]);
    }

    // ─────────────── EXISTING ROUTES (preserved) ───────────────

    #[Route('/inventory', name: 'admin_inventory')]
    public function inventory(Request $request, OffreEmploiRepository $offreEmploiRepository, CandidatureRepository $candidatureRepository): Response
    {
        $offres       = $offreEmploiRepository->findBy([], ['date_publication' => 'DESC', 'id' => 'DESC']);
        $candidatures = $candidatureRepository->findBy([], ['date_candidature' => 'DESC', 'id' => 'DESC']);
        $search       = trim((string) $request->query->get('q', ''));

        if ($search !== '') {
            $searchLower  = mb_strtolower($search);
            $offres       = array_values(array_filter($offres, static function (OffreEmploi $offre) use ($searchLower): bool {
                foreach ([$offre->getTitre(), $offre->getDepartement(), $offre->getTypeContrat(), $offre->getDescription()] as $v) {
                    if ($v !== null && str_contains(mb_strtolower($v), $searchLower)) {
                        return true;
                    }
                }
                return false;
            }));
            $candidatures = array_values(array_filter($candidatures, static function (Candidature $candidature) use ($searchLower): bool {
                $user  = $candidature->getUser();
                $offre = $candidature->getOffreEmploi();
                foreach ([$candidature->getStatut(), $candidature->getCv(), $user?->getNom(), $user?->getPrenom(), $user?->getEmail(), $offre?->getTitre()] as $v) {
                    if ($v !== null && str_contains(mb_strtolower($v), $searchLower)) {
                        return true;
                    }
                }
                return false;
            }));
        }

        $totalPostes            = array_reduce($offres, static fn (int $c, OffreEmploi $o): int => $c + ($o->getNombrePostes() ?? 0), 0);
        $candidaturesEnAttente  = count(array_filter($candidatures, static fn (Candidature $c): bool => $c->getStatut() === 'En attente'));

        return $this->render('admin/inventory/index.html.twig', [
            'offres'       => $offres,
            'candidatures' => $candidatures,
            'search'       => $search,
            'stats'        => [
                'offres'                  => count($offres),
                'candidatures'            => count($candidatures),
                'postes'                  => $totalPostes,
                'candidatures_en_attente' => $candidaturesEnAttente,
            ],
        ]);
    }

    #[Route('/offres/new', name: 'admin_offre_new')]
    public function newOffre(Request $request, EntityManagerInterface $entityManager): Response
    {
        $offre = new OffreEmploi();
        $offre->setDatePublication(new \DateTime());
        $user = $this->getUser();
        if ($user instanceof User) {
            $offre->setUser($user);
        }
        $form = $this->createForm(OffreEmploiType::class, $offre);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($offre);
            $entityManager->flush();
            $this->addFlash('success', "L'offre d'emploi a été créée.");
            return $this->redirectToRoute('admin_offre_show', ['id' => $offre->getId()]);
        }
        return $this->render('admin/offre_emploi/new.html.twig', ['form' => $form->createView(), 'offre' => $offre]);
    }

    #[Route('/offres/{id}', name: 'admin_offre_show', requirements: ['id' => '\d+'])]
    public function showOffre(OffreEmploi $offre): Response
    {
        return $this->render('admin/offre_emploi/show.html.twig', ['offre' => $offre]);
    }

    #[Route('/offres/{id}/edit', name: 'admin_offre_edit', requirements: ['id' => '\d+'])]
    public function editOffre(Request $request, OffreEmploi $offre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OffreEmploiType::class, $offre);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$offre->getUser()) {
                $user = $this->getUser();
                if ($user instanceof User) {
                    $offre->setUser($user);
                }
            }
            $entityManager->flush();
            $this->addFlash('success', "L'offre d'emploi a été mise à jour.");
            return $this->redirectToRoute('admin_offre_show', ['id' => $offre->getId()]);
        }
        return $this->render('admin/offre_emploi/edit.html.twig', ['form' => $form->createView(), 'offre' => $offre]);
    }

    #[Route('/offres/{id}/delete', name: 'admin_offre_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteOffre(Request $request, OffreEmploi $offre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_offre_' . $offre->getId(), (string) $request->request->get('_token'))) {
            if ($offre->getCandidatures()->count() > 0 || $offre->getQuizs()->count() > 0) {
                $this->addFlash('danger', "Suppression impossible: l'offre est liée à des candidatures ou des quiz.");
            } else {
                $entityManager->remove($offre);
                $entityManager->flush();
                $this->addFlash('success', "L'offre d'emploi a été supprimée.");
            }
        }
        return $this->redirectToRoute('admin_inventory');
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
            $this->addFlash('success', 'La candidature a été créée.');
            return $this->redirectToRoute('admin_candidature_show', ['id' => $candidature->getId()]);
        }
        return $this->render('admin/candidature/new.html.twig', ['form' => $form->createView(), 'candidature' => $candidature]);
    }

    #[Route('/candidatures/{id}', name: 'admin_candidature_show', requirements: ['id' => '\d+'])]
    public function showCandidature(Candidature $candidature): Response
    {
        return $this->render('admin/candidature/show.html.twig', ['candidature' => $candidature]);
    }

    #[Route('/candidatures/{id}/edit', name: 'admin_candidature_edit', requirements: ['id' => '\d+'])]
    public function editCandidature(Request $request, Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CandidatureType::class, $candidature);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La candidature a été mise à jour.');
            return $this->redirectToRoute('admin_candidature_show', ['id' => $candidature->getId()]);
        }
        return $this->render('admin/candidature/edit.html.twig', ['form' => $form->createView(), 'candidature' => $candidature]);
    }

    #[Route('/candidatures/{id}/delete', name: 'admin_candidature_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteCandidature(Request $request, Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_candidature_' . $candidature->getId(), (string) $request->request->get('_token'))) {
            if ($candidature->getEntretiens()->count() > 0) {
                $this->addFlash('danger', 'Suppression impossible: la candidature est liée à des entretiens.');
            } else {
                $entityManager->remove($candidature);
                $entityManager->flush();
                $this->addFlash('success', 'La candidature a été supprimée.');
            }
        }
        return $this->redirectToRoute('admin_inventory');
    }

    #[Route('/product/create', name: 'admin_product_create')]
    public function createProduct(): Response
    {
        return $this->redirectToRoute('admin_publication_index');
    }

    #[Route('/reports', name: 'admin_reports')]
    public function reports(): Response
    {
        return $this->render('admin/reports/index.html.twig');
    }

    #[Route('/docs', name: 'admin_docs')]
    public function docs(): Response
    {
        return $this->render('admin/docs/index.html.twig');
    }

    #[Route('/publications', name: 'admin_publication_index', methods: ['GET'])]
    public function publicationIndex(Request $request, PublicationRepository $publicationRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));

        return $this->render('admin/publication/index.html.twig', [
            'publications' => $publicationRepository->searchByKeyword($search),
            'search' => $search,
        ]);
    }

    #[Route('/publications/new', name: 'admin_publication_new', methods: ['GET', 'POST'])]
    public function publicationNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication->setDatePublication(new \DateTime());
            $user = $this->getUser();
            if ($user instanceof User) {
                $publication->setUser($user);
            }

            $entityManager->persist($publication);
            $entityManager->flush();
            $this->addFlash('success', 'Publication creee avec succes.');

            return $this->redirectToRoute('admin_publication_index');
        }

        return $this->render('admin/publication/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/publications/{id}/edit', name: 'admin_publication_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function publicationEdit(Request $request, Publication $publication, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$publication->getDatePublication()) {
                $publication->setDatePublication(new \DateTime());
            }

            $entityManager->flush();
            $this->addFlash('success', 'Publication mise a jour.');

            return $this->redirectToRoute('admin_publication_index');
        }

        return $this->render('admin/publication/edit.html.twig', [
            'form' => $form->createView(),
            'publication' => $publication,
        ]);
    }

    #[Route('/publications/{id}/delete', name: 'admin_publication_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publicationDelete(Request $request, Publication $publication, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_publication_' . $publication->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($publication);
            $entityManager->flush();
            $this->addFlash('success', 'Publication supprimee.');
        }

        return $this->redirectToRoute('admin_publication_index');
    }

    #[Route('/commentaires/new', name: 'admin_commentaire_new', methods: ['GET', 'POST'])]
    public function commentaireNew(Request $request, EntityManagerInterface $entityManager, PublicationRepository $publicationRepository): Response
    {
        $commentaire = new Commentaire();
        $publicationId = (int) $request->query->get('publication');
        if ($publicationId > 0) {
            $publication = $publicationRepository->find($publicationId);
            if ($publication instanceof Publication) {
                $commentaire->setPublication($publication);
            }
        }

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$commentaire->getPublication()) {
                $this->addFlash('danger', 'Selectionnez une publication.');
                return $this->redirectToRoute('admin_publication_index');
            }

            $commentaire->setDateCommentaire(new \DateTime());
            $user = $this->getUser();
            if ($user instanceof User) {
                $commentaire->setUser($user);
            }

            $entityManager->persist($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire ajoute.');

            return $this->redirectToRoute('admin_publication_index');
        }

        return $this->render('admin/commentaire/new.html.twig', [
            'form' => $form->createView(),
            'publication' => $commentaire->getPublication(),
        ]);
    }

    #[Route('/commentaires/{id}/edit', name: 'admin_commentaire_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function commentaireEdit(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$commentaire->getDateCommentaire()) {
                $commentaire->setDateCommentaire(new \DateTime());
            }

            $entityManager->flush();
            $this->addFlash('success', 'Commentaire mis a jour.');

            return $this->redirectToRoute('admin_publication_index');
        }

        return $this->render('admin/commentaire/edit.html.twig', [
            'form' => $form->createView(),
            'commentaire' => $commentaire,
        ]);
    }

    #[Route('/commentaires/{id}/delete', name: 'admin_commentaire_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function commentaireDelete(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_commentaire_' . $commentaire->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire supprime.');
        }

        return $this->redirectToRoute('admin_publication_index');
    }
}
