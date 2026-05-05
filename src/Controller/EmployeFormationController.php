<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\User;
use App\Form\FormationType;
use App\Repository\FormationRepository;
use App\Service\QrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employ/formation')]
class EmployeFormationController extends AbstractController
{
    #[Route('/', name: 'employ_formation_index', methods: ['GET'])]
    public function index(Request $request, FormationRepository $formationRepository): Response
    {
        $query = $request->query->get('q');
        $sortField = $request->query->get('sort');
        $sortOrder = $request->query->get('order', 'ASC');

        $formations = $formationRepository->searchAndSort($query, $sortField, $sortOrder);
        
        // QR code functionality removed as requested

        return $this->render('employ/formation/index.html.twig', [
            'formations' => $formations,
            'query' => $query,
            'sort' => $sortField,
            'order' => $sortOrder,
        ]);
    }

    #[Route('/new', name: 'employ_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formation = new Formation();

        // Get the current logged-in user
        $user = $this->getUser();
        if ($user instanceof User) {
            $formation->setUser($user);
        }

        $form = $this->createForm(FormationType::class, $formation, [
            'validation_groups' => ['Default', 'creation']
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Simple validation for localisation requirement
            $type = $form->get('type')->getData();
            $localisation = $form->get('localisation')->getData();
            
            if (($type === 'Présentiel' || $type === 'Hybride') && empty($localisation)) {
                $this->addFlash('error', 'La localisation est requise pour les formations de type Présentiel ou Hybride.');
                return $this->render('employ/formation/new.html.twig', [
                    'formation' => $formation,
                    'form' => $form->createView(),
                ]);
            }
            
            $entityManager->persist($formation);
            $entityManager->flush();
            
            $this->addFlash('success', 'La formation a été créée avec succès.');
            return $this->redirectToRoute('employ_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('employ/formation/new.html.twig', [
            'formation' => $formation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'employ_formation_show', methods: ['GET'])]
    public function show(Formation $formation): Response
    {
        // Extract coordinates from localisation (simple regex)
        $coordinates = null;
        $localisation = $formation->getLocalisation() ?? '';
        if (preg_match('/(\-?\d+\.\d+),\s*(\-?\d+\.\d+)/', $localisation, $matches)) {
            $coordinates = [
                'latitude' => (float) $matches[1],
                'longitude' => (float) $matches[2]
            ];
        }
        
        // Check if QR code exists
        $qrCodePath = 'qrcodes/formation_' . $formation->getId() . '.png';
        $fullQrPath = $this->getParameter('kernel.project_dir') . '/public/' . $qrCodePath;
        
        if (!file_exists($fullQrPath)) {
            $qrCodePath = null;
        }
        
        return $this->render('employ/formation/show.html.twig', [
            'formation' => $formation,
            'coordinates' => $coordinates,
            'qrCodePath' => $qrCodePath,
        ]);
    }

    #[Route('/{id}/edit', name: 'employ_formation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La formation a été mise à jour avec succès.');
            return $this->redirectToRoute('employ_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('employ/formation/edit.html.twig', [
            'formation' => $formation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'employ_formation_delete', methods: ['POST'])]
    public function delete(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($formation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('employ_formation_index', [], Response::HTTP_SEE_OTHER);
    }
}
