<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Entity\User;
use App\Form\OffreEmploiType;
use App\Repository\CandidatureRepository;
use App\Repository\OffreEmploiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\CongeRepository;
use App\Repository\UserRepository;
use App\Repository\AbsenceRepository;
use App\Repository\FormationRepository;
use App\Entity\Conge;
use App\Entity\Absence;
use App\Entity\Formation;
use App\Entity\Publication;
use App\Entity\Commentaire;
use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use App\Service\FacebookJobPublisher;
use App\Service\OfferDeletionService;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(FormationRepository $formationRepository): Response
    {
        // Get participation data for charts
        $formations = $formationRepository->findAll();
        
        // Prepare data for bar chart: participants per training
        $participantsPerTraining = [];
        $trainingLabels = [];
        
        foreach ($formations as $formation) {
            $participantsCount = count($formation->getParticipations());
            $participantsPerTraining[] = $participantsCount;
            $trainingLabels[] = $formation->getSujet() ?? 'Formation ' . $formation->getId();
        }
        
        // Prepare data for pie chart: results distribution
        $resultsDistribution = [
            'validé' => 0,
            'non validé' => 0,
            'en cours' => 0
        ];
        
        foreach ($formations as $formation) {
            foreach ($formation->getParticipations() as $participation) {
                $resultat = $participation->getResultat() ?? 'en cours';
                if (isset($resultsDistribution[$resultat])) {
                    $resultsDistribution[$resultat]++;
                }
            }
        }
        
        return $this->render('admin/dashboard/index.html.twig', [
            'participantsPerTraining' => $participantsPerTraining,
            'trainingLabels' => $trainingLabels,
            'resultsDistribution' => $resultsDistribution,
        ]);
    }

    #[Route('/inventory', name: 'admin_inventory')]
    public function inventory(Request $request, OffreEmploiRepository $offreEmploiRepository, CandidatureRepository $candidatureRepository, PaginatorInterface $paginator): Response
    {
        $offres = $offreEmploiRepository->findBy([], ['date_publication' => 'DESC', 'id' => 'DESC']);
        $candidatures = $candidatureRepository->findBy([], ['date_candidature' => 'DESC', 'id' => 'DESC']);
        $search = trim((string) $request->query->get('q', ''));

        if ($search !== '') {
            $searchLower = mb_strtolower($search);

            $offres = array_values(array_filter(
                $offres,
                static function (OffreEmploi $offre) use ($searchLower): bool {
                    $haystacks = [
                        $offre->getTitre(),
                        $offre->getDepartement(),
                        $offre->getTypeContrat(),
                        $offre->getDescription(),
                    ];

                    foreach ($haystacks as $value) {
                        if ($value !== null && str_contains(mb_strtolower($value), $searchLower)) {
                            return true;
                        }
                    }

                    return false;
                }
            ));

            $candidatures = array_values(array_filter(
                $candidatures,
                static function (Candidature $candidature) use ($searchLower): bool {
                    $user = $candidature->getUser();
                    $offre = $candidature->getOffreEmploi();
                    $haystacks = [
                        $candidature->getStatut(),
                        $candidature->getCv(),
                        $user?->getNom(),
                        $user?->getPrenom(),
                        $user?->getEmail(),
                        $offre?->getTitre(),
                    ];

                    foreach ($haystacks as $value) {
                        if ($value !== null && str_contains(mb_strtolower($value), $searchLower)) {
                            return true;
                        }
                    }

                    return false;
                }
            ));
        }

        $totalPostes = array_reduce(
            $offres,
            static fn (int $carry, OffreEmploi $offre): int => $carry + ($offre->getNombrePostes() ?? 0),
            0
        );

        $candidaturesEnAttente = count(array_filter(
            $candidatures,
            static fn (Candidature $candidature): bool => $candidature->getStatut() === 'En attente'
        ));

        $monthlyOffers = $this->buildMonthlySeries(
            $offres,
            static fn (OffreEmploi $offre): ?\DateTimeInterface => $offre->getDatePublication()
        );

        $monthlyApplications = $this->buildMonthlySeries(
            $candidatures,
            static fn (Candidature $candidature): ?\DateTimeInterface => $candidature->getDateCandidature()
        );

        $contractCounts = [];
        foreach ($offres as $offre) {
            $label = $offre->getTypeContrat() ?: 'Non precise';
            $contractCounts[$label] = ($contractCounts[$label] ?? 0) + ($offre->getNombrePostes() ?? 0);
        }
        arsort($contractCounts);
        $contractCounts = array_slice($contractCounts, 0, 4, true);

        $statusCounts = [
            'En attente' => 0,
            'En cours' => 0,
            'Acceptee' => 0,
            'Refusee' => 0,
        ];
        foreach ($candidatures as $candidature) {
            $status = $candidature->getStatut() ?: 'En attente';
            if (!array_key_exists($status, $statusCounts)) {
                $statusCounts[$status] = 0;
            }
            $statusCounts[$status]++;
        }

        $offresPagination = $paginator->paginate(
            $offres,
            max(1, $request->query->getInt('offres_page', 1)),
            8,
            [
                'pageParameterName' => 'offres_page',
            ]
        );

        $candidaturesPagination = $paginator->paginate(
            $candidatures,
            max(1, $request->query->getInt('candidatures_page', 1)),
            8,
            [
                'pageParameterName' => 'candidatures_page',
            ]
        );

        return $this->render('admin/recrutment/recrutment.html.twig', [
            'offres' => $offresPagination,
            'candidatures' => $candidaturesPagination,
            'search' => $search,
            'stats' => [
                'offres' => count($offres),
                'candidatures' => count($candidatures),
                'postes' => $totalPostes,
                'candidatures_en_attente' => $candidaturesEnAttente,
            ],
            'kpi_charts' => [
                'offers' => $monthlyOffers,
                'applications' => $monthlyApplications,
                'contracts' => [
                    'labels' => array_keys($contractCounts),
                    'series' => array_values($contractCounts),
                ],
                'statuses' => [
                    'labels' => array_keys($statusCounts),
                    'series' => array_values($statusCounts),
                ],
            ],
        ]);
    }

    /**
     * @template T
     * @param array<int, T> $items
     * @param callable(T): ?\DateTimeInterface $dateResolver
     * @return array{labels:array<int,string>,series:array<int,int>}
     */
    private function buildMonthlySeries(array $items, callable $dateResolver, int $months = 6): array
    {
        $labels = [];
        $counts = [];
        $now = new \DateTimeImmutable('first day of this month');

        for ($offset = $months - 1; $offset >= 0; --$offset) {
            $month = $now->modify(sprintf('-%d months', $offset));
            $key = $month->format('Y-m');
            $labels[$key] = $month->format('M Y');
            $counts[$key] = 0;
        }

        foreach ($items as $item) {
            $date = $dateResolver($item);
            if (!$date instanceof \DateTimeInterface) {
                continue;
            }

            $key = $date->format('Y-m');
            if (array_key_exists($key, $counts)) {
                $counts[$key]++;
            }
        }

        return [
            'labels' => array_values($labels),
            'series' => array_values($counts),
        ];
    }

    #[Route('/offres/new', name: 'admin_offre_new')]
    public function newOffre(Request $request, EntityManagerInterface $entityManager, FacebookJobPublisher $facebookJobPublisher): Response
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

            $this->addFlash('success', "L'offre d'emploi a ete creee.");

            if ($facebookJobPublisher->isConfigured()) {
                try {
                    $facebookJobPublisher->publishOffer($offre);
                    $this->addFlash('success', 'L offre a aussi ete publiee automatiquement sur Facebook.');
                } catch (\Throwable $exception) {
                    $this->addFlash('warning', 'L offre a ete creee, mais la publication Facebook a echoue: '.$exception->getMessage());
                }
            } else {
                $this->addFlash('info', 'Publication Facebook non configuree. Renseignez les variables FACEBOOK_PAGE_ID et FACEBOOK_PAGE_ACCESS_TOKEN pour l activer.');
            }

            return $this->redirectToRoute('admin_offre_show', ['id' => $offre->getId()]);
        }

        return $this->render('admin/offre_emploi/new.html.twig', [
            'form' => $form->createView(),
            'offre' => $offre,
        ]);
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

            $this->addFlash('success', "L'offre d'emploi a ete mise a jour.");

            return $this->redirectToRoute('admin_offre_show', ['id' => $offre->getId()]);
        }

        return $this->render('admin/offre_emploi/edit.html.twig', [
            'form' => $form->createView(),
            'offre' => $offre,
        ]);
    }

    #[Route('/offres/{id}/delete', name: 'admin_offre_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteOffre(Request $request, OffreEmploi $offre, OfferDeletionService $offerDeletionService): Response
    {
        if ($this->isCsrfTokenValid('delete_offre_'.$offre->getId(), (string) $request->request->get('_token'))) {
            $offerDeletionService->deleteOfferWithRelations($offre);
            $this->addFlash('success', "L'offre d'emploi ainsi que ses candidatures, entretiens et quiz lies ont ete supprimes.");
        }

        return $this->redirectToRoute('admin_inventory');
    }

    #[Route('/product/create', name: 'admin_product_create')]
    public function createProduct(): Response
    {
        return $this->render('admin/product/create.html.twig');
    }

    #[Route('/reports', name: 'admin_reports')]
    public function reports(): Response
    {
        return $this->render('admin/reports/index.html.twig');
    }
//route
    #[Route('/docs', name: 'admin_docs')]
    public function docs(): Response
    {
        return $this->render('admin/docs/index.html.twig');
    }

    #[Route('/absence', name: 'admin_absence')]
    public function absence(Request $request, AbsenceRepository $absenceRepository, EntityManagerInterface $em): Response
    {
        $searchQuery = $request->query->get('q');

        if ($searchQuery) {
            $qb = $em->createQueryBuilder();
            $qb->select('a', 'u')
                ->from(Absence::class, 'a')
                ->leftJoin('a.user', 'u')
                ->where(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.nom', ':query'),
                        $qb->expr()->like('u.prenom', ':query'),
                        $qb->expr()->like('a.type_absence', ':query'),
                        $qb->expr()->like('a.statut', ':query')
                    )
                )
                ->setParameter('query', '%' . $searchQuery . '%')
                ->orderBy('a.date_debut', 'DESC');

            $absences = $qb->getQuery()->getResult();
        } else {
            $absences = $absenceRepository->findAll();
        }

        return $this->render('admin/absence/index.html.twig', [
            'absences' => $absences,
        ]);
    }

    #[Route('/absence/new', name: 'admin_absence_new', methods: ['GET', 'POST'])]
    public function newAbsence(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $errors = [];
        $MAX_DUREE = 30;

        if ($request->isMethod('POST')) {
            $employeId = $request->request->get('employe_id');
            $typeAbsence = $request->request->get('type_absence');
            $dateDebutStr = $request->request->get('date_debut');
            $dateFinStr = $request->request->get('date_fin');
            $statut = $request->request->get('statut', 'en_attente');
            $motif = $request->request->get('motif', '');

            // Validation employé
            if (empty($employeId)) {
                $errors[] = 'Veuillez sélectionner un employé.';
            }

            // Validation type
            $typesValides = ['maladie', 'accident', 'absence_injustifiee', 'retard', 'formation', 'autre'];
            if (empty($typeAbsence)) {
                $errors[] = 'Veuillez sélectionner un type d\'absence.';
            } elseif (!in_array($typeAbsence, $typesValides)) {
                $errors[] = 'Le type d\'absence sélectionné n\'est pas valide.';
            }

            // Validation statut
            $statutsValides = ['en_attente', 'approuve', 'refuse'];
            if (empty($statut)) {
                $errors[] = 'Le statut est obligatoire.';
            } elseif (!in_array($statut, $statutsValides)) {
                $errors[] = 'Le statut sélectionné n\'est pas valide.';
            }

            // Validation dates
            $dateDebut = null;
            $dateFin = null;
            $duree = 0;

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
                    // Validation: année max 2026
                    $yearDebut = (int)$dateDebut->format('Y');
                    if ($yearDebut > 2026) {
                        $errors[] = 'La date de début ne peut pas être en 2027 ou au-delà.';
                    }
                } catch (\Exception $e) {
                    $errors[] = 'La date de début n\'est pas valide.';
                }
            }

            if (empty($dateFinStr)) {
                $errors[] = 'La date de fin est obligatoire.';
            } else {
                try {
                    $dateFin = new \DateTime($dateFinStr);
                    // Validation: année max 2026
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
                $duree = $diff->days + 1;
                if ($duree > $MAX_DUREE) {
                    $errors[] = 'La durée maximale d\'une absence est de ' . $MAX_DUREE . ' jours.';
                }
            }

            // Validation motif obligatoire pour maladie > 3 jours
            if ($typeAbsence === 'maladie' && $duree > 3 && empty(trim($motif))) {
                $errors[] = 'Le motif est obligatoire pour les absences maladie de plus de 3 jours.';
            }

            // Si pas d'erreurs, créer l'absence
            if (empty($errors)) {
                $absence = new Absence();
                $absence->setDateDebut($dateDebut);
                $absence->setDateFin($dateFin);
                $absence->setTypeAbsence($typeAbsence);
                $absence->setStatut($statut);

                $user = $userRepository->find($employeId);
                if (!$user) {
                    $errors[] = 'L\'employé sélectionné n\'existe pas.';
                } else {
                    $absence->setUser($user);
                    $em->persist($absence);
                    $em->flush();

                    $this->addFlash('success', 'Absence créée avec succès.');
                    return $this->redirectToRoute('admin_absence');
                }
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('admin/absence/new.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/absence/{id}/edit', name: 'admin_absence_edit', methods: ['GET', 'POST'])]
    public function editAbsence(Absence $absence, Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $errors = [];

        if ($request->isMethod('POST')) {
            $employeId = $request->request->get('employe_id');
            $typeAbsence = $request->request->get('type_absence');
            $dateDebutStr = $request->request->get('date_debut');
            $dateFinStr = $request->request->get('date_fin');
            $statut = $request->request->get('statut');

            if (empty($employeId)) {
                $errors[] = 'Veuillez sélectionner un employé.';
            }
            if (empty($typeAbsence)) {
                $errors[] = 'Veuillez sélectionner un type d\'absence.';
            }
            if (empty($statut)) {
                $errors[] = 'Le statut est obligatoire.';
            }
            if (empty($dateDebutStr)) {
                $errors[] = 'La date de début est obligatoire.';
            } else {
                try {
                    $dateDebut = new \DateTime($dateDebutStr);
                    $today = new \DateTime('today');
                    $today->setTime(0, 0, 0);
                    $dateDebut->setTime(0, 0, 0);
                    $currentDateDebut = $absence->getDateDebut();
                    if ($currentDateDebut) {
                        $currentDateDebut->setTime(0, 0, 0);
                        if ($dateDebut != $currentDateDebut && $dateDebut < $today) {
                            $errors[] = 'La nouvelle date de début ne peut pas être dans le passé.';
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = 'La date de début n\'est pas valide.';
                }
            }
            if (empty($dateFinStr)) {
                $errors[] = 'La date de fin est obligatoire.';
            } else {
                try {
                    $dateFin = new \DateTime($dateFinStr);
                    if (isset($dateDebut) && $dateFin <= $dateDebut) {
                        $errors[] = 'La date de fin doit être après la date de début.';
                    }
                } catch (\Exception $e) {
                    $errors[] = 'La date de fin n\'est pas valide.';
                }
            }

            if (empty($errors)) {
                $absence->setDateDebut(new \DateTime($dateDebutStr));
                $absence->setDateFin(new \DateTime($dateFinStr));
                $absence->setTypeAbsence($typeAbsence);
                $absence->setStatut($statut);

                $user = $userRepository->find($employeId);
                if (!$user) {
                    $errors[] = 'L\'employé sélectionné n\'existe pas.';
                } else {
                    $absence->setUser($user);
                    $em->flush();

                    $this->addFlash('success', 'Absence modifiée avec succès.');
                    return $this->redirectToRoute('admin_absence');
                }
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('admin/absence/edit.html.twig', [
            'absence' => $absence,
            'users' => $users,
        ]);
    }

    #[Route('/absence/{id}/delete', name: 'admin_absence_delete', methods: ['POST'])]
    public function deleteAbsence(Absence $absence, EntityManagerInterface $em): Response
    {
        $em->remove($absence);
        $em->flush();

        $this->addFlash('success', 'Absence supprimée avec succès.');
        return $this->redirectToRoute('admin_absence');
    }

    #[Route('/conge', name: 'admin_conge')]
    public function conge(Request $request, CongeRepository $congeRepository, EntityManagerInterface $em): Response
    {
        $searchQuery = $request->query->get('q');

        if ($searchQuery) {
            // Recherche avec filtre
            $qb = $em->createQueryBuilder();
            $qb->select('c', 'a', 'u')
                ->from(Conge::class, 'c')
                ->leftJoin('c.absence', 'a')
                ->leftJoin('a.user', 'u')
                ->where(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.nom', ':query'),
                        $qb->expr()->like('u.prenom', ':query'),
                        $qb->expr()->like('a.type_absence', ':query'),
                        $qb->expr()->like('a.statut', ':query')
                    )
                )
                ->setParameter('query', '%' . $searchQuery . '%')
                ->orderBy('c.date_demande', 'DESC');

            $conges = $qb->getQuery()->getResult();
        } else {
            // Récupérer tous les congés avec leurs relations absence et user
            $qb = $em->createQueryBuilder();
            $qb->select('c', 'a', 'u')
                ->from(Conge::class, 'c')
                ->leftJoin('c.absence', 'a')
                ->leftJoin('a.user', 'u')
                ->orderBy('c.date_demande', 'DESC');

            $conges = $qb->getQuery()->getResult();
        }

        return $this->render('admin/conge/index.html.twig', [
            'conges' => $conges,
        ]);
    }

    #[Route('/conge/new', name: 'admin_conge_new', methods: ['GET', 'POST'])]
    public function newConge(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $conge = new Conge();
        $conge->setDateDemande(new \DateTime());

        $users = $userRepository->findAll();
        $errors = [];

        if ($request->isMethod('POST')) {
            // Validation des données
            $employeId = $request->request->get('employe_id');
            $typeAbsence = $request->request->get('type_absence');
            $dateDebutStr = $request->request->get('date_debut');
            $dateFinStr = $request->request->get('date_fin');

            // Validation employé
            if (empty($employeId)) {
                $errors[] = 'Veuillez sélectionner un employé.';
            }

            // Validation type
            if (empty($typeAbsence)) {
                $errors[] = 'Veuillez sélectionner un type de congé.';
            }

            // Validation dates
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
                } catch (\Exception $e) {
                    $errors[] = 'La date de début n\'est pas valide.';
                }
            }

            if (empty($dateFinStr)) {
                $errors[] = 'La date de fin est obligatoire.';
            } else {
                try {
                    $dateFin = new \DateTime($dateFinStr);

                    if (isset($dateDebut) && $dateFin <= $dateDebut) {
                        $errors[] = 'La date de fin doit être après la date de début.';
                    }
                } catch (\Exception $e) {
                    $errors[] = 'La date de fin n\'est pas valide.';
                }
            }

            // Si pas d'erreurs, créer le congé
            if (empty($errors)) {
                $absence = new Absence();
                $absence->setDateDebut(new \DateTime($dateDebutStr));
                $absence->setDateFin(new \DateTime($dateFinStr));
                $absence->setTypeAbsence($typeAbsence);
                $absence->setStatut('en_attente');

                $user = $userRepository->find($employeId);
                if (!$user) {
                    $errors[] = 'L\'employé sélectionné n\'existe pas.';
                } else {
                    $absence->setUser($user);
                    $em->persist($absence);

                    $conge->setAbsence($absence);
                    $conge->setUser($this->getUser());

                    $em->persist($conge);
                    $em->flush();

                    $this->addFlash('success', 'Demande de congé créée avec succès.');
                    return $this->redirectToRoute('admin_conge');
                }
            }

            // Si erreurs, les afficher
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('admin/conge/new.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/conge/{id}/edit', name: 'admin_conge_edit', methods: ['GET', 'POST'])]
    public function editConge(Conge $conge, Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $errors = [];

        if ($request->isMethod('POST')) {
            $employeId = $request->request->get('employe_id');
            $typeAbsence = $request->request->get('type_absence');
            $dateDebutStr = $request->request->get('date_debut');
            $dateFinStr = $request->request->get('date_fin');
            $statut = $request->request->get('statut');

            // Validation employé
            if (empty($employeId)) {
                $errors[] = 'Veuillez sélectionner un employé.';
            }

            // Validation type
            if (empty($typeAbsence)) {
                $errors[] = 'Veuillez sélectionner un type de congé.';
            }

            // Validation statut
            if (empty($statut)) {
                $errors[] = 'Le statut est obligatoire.';
            } elseif (!in_array($statut, ['en_attente', 'approuve', 'refuse'])) {
                $errors[] = 'Le statut sélectionné n\'est pas valide.';
            }

            // Validation dates
            if (empty($dateDebutStr)) {
                $errors[] = 'La date de début est obligatoire.';
            } else {
                try {
                    $dateDebut = new \DateTime($dateDebutStr);
                    $today = new \DateTime('today');
                    $today->setTime(0, 0, 0);
                    $dateDebut->setTime(0, 0, 0);

                    // Pour l'édition, vérifier si la date a changé
                    $absence = $conge->getAbsence();
                    $currentDateDebut = $absence ? $absence->getDateDebut() : null;

                    if ($currentDateDebut) {
                        $currentDateDebut->setTime(0, 0, 0);
                        // Si la date a changé et est dans le passé
                        if ($dateDebut != $currentDateDebut && $dateDebut < $today) {
                            $errors[] = 'La nouvelle date de début ne peut pas être dans le passé.';
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = 'La date de début n\'est pas valide.';
                }
            }

            if (empty($dateFinStr)) {
                $errors[] = 'La date de fin est obligatoire.';
            } else {
                try {
                    $dateFin = new \DateTime($dateFinStr);

                    if (isset($dateDebut) && $dateFin <= $dateDebut) {
                        $errors[] = 'La date de fin doit être après la date de début.';
                    }
                } catch (\Exception $e) {
                    $errors[] = 'La date de fin n\'est pas valide.';
                }
            }

            // Si pas d'erreurs, mettre à jour
            if (empty($errors)) {
                $absence = $conge->getAbsence();
                $absence->setDateDebut(new \DateTime($dateDebutStr));
                $absence->setDateFin(new \DateTime($dateFinStr));
                $absence->setTypeAbsence($typeAbsence);
                $absence->setStatut($statut);

                $user = $userRepository->find($employeId);
                if (!$user) {
                    $errors[] = 'L\'employé sélectionné n\'existe pas.';
                } else {
                    $absence->setUser($user);

                    $conge->setCommentaireValidation($request->request->get('commentaire_validation'));

                    if ($statut === 'approuve' && !$conge->getDateValidation()) {
                        $conge->setDateValidation(new \DateTime());
                    }

                    $em->flush();

                    $this->addFlash('success', 'Demande de congé modifiée avec succès.');
                    return $this->redirectToRoute('admin_conge');
                }
            }

            // Si erreurs, les afficher
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('admin/conge/edit.html.twig', [
            'conge' => $conge,
            'users' => $users,
        ]);
    }

    #[Route('/conge/{id}/delete', name: 'admin_conge_delete', methods: ['POST'])]
    public function deleteConge(Conge $conge, EntityManagerInterface $em): Response
    {
        $absence = $conge->getAbsence();
        if ($absence) {
            $em->remove($absence);
        }
        $em->remove($conge);
        $em->flush();

        $this->addFlash('success', 'Demande de congé supprimée avec succès.');
        return $this->redirectToRoute('admin_conge');
    }

    #[Route('/formation', name: 'admin_formation')]
    public function formation(Request $request, FormationRepository $formationRepository): Response
    {
        $query = $request->query->get('q');
        $sortField = $request->query->get('sort');
        $sortOrder = $request->query->get('order', 'ASC');

        $formations = $formationRepository->searchAndSort($query, $sortField, $sortOrder);

        return $this->render('admin/formation/index.html.twig', [
            'formations' => $formations,
            'query' => $query,
            'sort' => $sortField,
            'order' => $sortOrder,
        ]);
    }

    #[Route('/formation/new', name: 'admin_formation_new', methods: ['GET', 'POST'])]
    public function newFormation(Request $request, EntityManagerInterface $em): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            $sujet = $request->request->get('sujet');
            $formateur = $request->request->get('formateur');
            $type = $request->request->get('type');
            $dateDebutStr = $request->request->get('date_debut');
            $duree = $request->request->get('duree');
            $localisation = $request->request->get('localisation');

            if (empty($sujet)) {
                $errors[] = 'Le sujet est obligatoire.';
            }
            if (empty($formateur)) {
                $errors[] = 'Le formateur est obligatoire.';
            }
            if (empty($type)) {
                $errors[] = 'Le type est obligatoire.';
            }
            if (empty($dateDebutStr)) {
                $errors[] = 'La date de début est obligatoire.';
            }
            if (empty($duree) || $duree <= 0) {
                $errors[] = 'La durée doit être supérieure à 0.';
            }

            if (empty($errors)) {
                $formation = new Formation();
                $formation->setSujet($sujet);
                $formation->setFormateur($formateur);
                $formation->setType($type);
                $formation->setDateDebut(new \DateTime($dateDebutStr));
                $formation->setDuree((int)$duree);
                $formation->setLocalisation($localisation);
                $formation->setUser($this->getUser());

                $em->persist($formation);
                $em->flush();

                $this->addFlash('success', 'Formation créée avec succès.');
                return $this->redirectToRoute('admin_formation');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('admin/formation/new.html.twig');
    }

    #[Route('/formation/{id}/edit', name: 'admin_formation_edit', methods: ['GET', 'POST'])]
    public function editFormation(Formation $formation, Request $request, EntityManagerInterface $em): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            $sujet = $request->request->get('sujet');
            $formateur = $request->request->get('formateur');
            $type = $request->request->get('type');
            $dateDebutStr = $request->request->get('date_debut');
            $duree = $request->request->get('duree');
            $localisation = $request->request->get('localisation');

            if (empty($sujet)) {
                $errors[] = 'Le sujet est obligatoire.';
            }
            if (empty($formateur)) {
                $errors[] = 'Le formateur est obligatoire.';
            }
            if (empty($type)) {
                $errors[] = 'Le type est obligatoire.';
            }
            if (empty($dateDebutStr)) {
                $errors[] = 'La date de début est obligatoire.';
            }
            if (empty($duree) || $duree <= 0) {
                $errors[] = 'La durée doit être supérieure à 0.';
            }

            if (empty($errors)) {
                $formation->setSujet($sujet);
                $formation->setFormateur($formateur);
                $formation->setType($type);
                $formation->setDateDebut(new \DateTime($dateDebutStr));
                $formation->setDuree((int)$duree);
                $formation->setLocalisation($localisation);

                $em->flush();

                $this->addFlash('success', 'Formation modifiée avec succès.');
                return $this->redirectToRoute('admin_formation');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('admin/formation/edit.html.twig', [
            'formation' => $formation,
        ]);
    }

    #[Route('/formation/{id}/delete', name: 'admin_formation_delete', methods: ['POST'])]
    public function deleteFormation(Formation $formation, EntityManagerInterface $em): Response
    {
        $em->remove($formation);
        $em->flush();

        $this->addFlash('success', 'Formation supprimée avec succès.');
        return $this->redirectToRoute('admin_formation');
    }

    #[Route('/publication', name: 'admin_publication')]
    public function publication(PublicationRepository $publicationRepository): Response
    {
        $publications = $publicationRepository->findBy([], ['date_publication' => 'DESC']);
        return $this->render('admin/publication/index.html.twig', [
            'publications' => $publications,
        ]);
    }

    #[Route('/publication/new', name: 'admin_publication_new', methods: ['GET', 'POST'])]
    public function newPublication(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $contenu = $request->request->get('contenu');
            $type = $request->request->get('type');

            $publication = new Publication();
            $publication->setContenu($contenu);
            $publication->setType($type);
            $publication->setDate_publication(new \DateTime());
            $publication->setUser($this->getUser());

            $em->persist($publication);
            $em->flush();

            $this->addFlash('success', 'Publication créée avec succès.');
            return $this->redirectToRoute('admin_publication');
        }

        return $this->render('admin/publication/new.html.twig');
    }

    #[Route('/publication/{id}/edit', name: 'admin_publication_edit', methods: ['GET', 'POST'])]
    public function editPublication(Publication $publication, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $publication->setContenu($request->request->get('contenu'));
            $publication->setType($request->request->get('type'));

            $em->flush();
            $this->addFlash('success', 'Publication modifiée avec succès.');
            return $this->redirectToRoute('admin_publication');
        }

        return $this->render('admin/publication/edit.html.twig', [
            'publication' => $publication,
        ]);
    }

    #[Route('/publication/{id}/delete', name: 'admin_publication_delete', methods: ['POST'])]
    public function deletePublication(Publication $publication, EntityManagerInterface $em): Response
    {
        $em->remove($publication);
        $em->flush();

        $this->addFlash('success', 'Publication supprimée avec succès.');
        return $this->redirectToRoute('admin_publication');
    }

    #[Route('/commentaire', name: 'admin_commentaire')]
    public function commentaire(CommentaireRepository $commentaireRepository): Response
    {
        $commentaires = $commentaireRepository->findBy([], ['date_commentaire' => 'DESC']);
        return $this->render('admin/commentaire/index.html.twig', [
            'commentaires' => $commentaires,
        ]);
    }

    #[Route('/commentaire/new', name: 'admin_commentaire_new', methods: ['GET', 'POST'])]
    public function newCommentaire(Request $request, EntityManagerInterface $em): Response
    {
        $publicationId = $request->query->get('publication');
        $publication = $em->getRepository(Publication::class)->find($publicationId);

        if ($request->isMethod('POST')) {
            $contenu = $request->request->get('contenu');
            $publicationId = $request->request->get('publication_id');
            $publication = $em->getRepository(Publication::class)->find($publicationId);

            $commentaire = new Commentaire();
            $commentaire->setContenu($contenu);
            $commentaire->setDate_commentaire(new \DateTime());
            $commentaire->setPublication($publication);
            $commentaire->setUser($this->getUser());

            $em->persist($commentaire);
            $em->flush();

            $this->addFlash('success', 'Commentaire ajouté avec succès.');
            return $this->redirectToRoute('admin_publication');
        }

        return $this->render('admin/commentaire/new.html.twig', [
            'publication' => $publication,
        ]);
    }

    #[Route('/commentaire/{id}/edit', name: 'admin_commentaire_edit', methods: ['GET', 'POST'])]
    public function editCommentaire(Commentaire $commentaire, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $commentaire->setContenu($request->request->get('contenu'));

            $em->flush();
            $this->addFlash('success', 'Commentaire modifié avec succès.');
            return $this->redirectToRoute('admin_publication');
        }

        return $this->render('admin/commentaire/edit.html.twig', [
            'commentaire' => $commentaire,
        ]);
    }

    #[Route('/commentaire/{id}/delete', name: 'admin_commentaire_delete', methods: ['POST'])]
    public function deleteCommentaire(Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        $em->remove($commentaire);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé avec succès.');
        return $this->redirectToRoute('admin_publication');
    }
}
