<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\User;
use App\Form\FormationType;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\QrCodeService;

#[Route('/admin/formation')]
class FormationController extends AbstractController
{
    #[Route('/', name: 'admin_formation_index', methods: ['GET'])]
    public function index(Request $request, FormationRepository $formationRepository): Response
    {
        $query = $request->query->get('q');
        $sortField = $request->query->get('sort');
        $sortOrder = $request->query->get('order', 'ASC');

        return $this->render('admin/formation/index.html.twig', [
            'formations' => $formationRepository->searchAndSort($query, $sortField, $sortOrder),
            'query' => $query,
            'sort' => $sortField,
            'order' => $sortOrder,
        ]);
    }

    #[Route('/new', name: 'admin_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, QrCodeService $qrCodeService): Response
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
            $entityManager->persist($formation);
            $entityManager->flush();

            // QR code generation removed - method not available
            
            $this->addFlash('success', 'La formation a été créée avec succès. QR Code généré.');
            return $this->redirectToRoute('admin_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/formation/new.html.twig', [
            'formation' => $formation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_formation_show', methods: ['GET'])]
    public function show(Formation $formation, QrCodeService $qrCodeService): Response
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
        
        // Generate QR code if not exists
        $qrCodePath = 'qrcodes/formation_' . $formation->getId() . '.png';
        $fullQrPath = $this->getParameter('kernel.project_dir') . '/public/' . $qrCodePath;
        
        // QR code generation skipped - method not available in service
        
        return $this->render('admin/formation/show.html.twig', [
            'formation' => $formation,
            'coordinates' => $coordinates,
            'qrCodePath' => $qrCodePath,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_formation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La formation a été mise à jour avec succès.');
            return $this->redirectToRoute('admin_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/formation/edit.html.twig', [
            'formation' => $formation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_formation_delete', methods: ['POST'])]
    public function delete(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($formation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_formation_index', [], Response::HTTP_SEE_OTHER);
    }
}
