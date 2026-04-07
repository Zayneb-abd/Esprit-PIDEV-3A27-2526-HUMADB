<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CongeRepository;
use App\Repository\UserRepository;
use App\Repository\AbsenceRepository;
use App\Repository\FormationRepository;
use App\Entity\Conge;
use App\Entity\Absence;
use App\Entity\Formation;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard/index.html.twig');
    }

    #[Route('/inventory', name: 'admin_inventory')]
    public function inventory(): Response
    {
        return $this->render('admin/inventory/index.html.twig');
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
            $conges = $congeRepository->findAll();
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
}
