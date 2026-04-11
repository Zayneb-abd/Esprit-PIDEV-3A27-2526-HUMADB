<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Entity\User;
use App\Form\CommentaireType;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employ')]
class EmployeController extends AbstractController
{
    #[Route('/', name: 'employ_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('employ/dashboard/index.html.twig');
    }

    #[Route('/inventory', name: 'employ_inventory')]
    public function inventory(): Response
    {
        return $this->render('employ/inventory/index.html.twig');
    }

    #[Route('/product/create', name: 'employ_product_create')]
    public function createProduct(): Response
    {
        return $this->redirectToRoute('employ_publication_index');
    }

    #[Route('/reports', name: 'employ_reports')]
    public function reports(): Response
    {
        return $this->render('employ/reports/index.html.twig');
    }

    #[Route('/docs', name: 'employ_docs')]
    public function docs(): Response
    {
        return $this->render('employ/docs/index.html.twig');
    }

    #[Route('/publications', name: 'employ_publication_index', methods: ['GET'])]
    public function publicationIndex(Request $request, PublicationRepository $publicationRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));

        return $this->render('employ/publication/index.html.twig', [
            'publications' => $publicationRepository->searchByKeyword($search),
            'search' => $search,
        ]);
    }

    #[Route('/publications/new', name: 'employ_publication_new', methods: ['GET', 'POST'])]
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

            return $this->redirectToRoute('employ_publication_index');
        }

        return $this->render('employ/publication/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/publications/{id}/edit', name: 'employ_publication_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
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

            return $this->redirectToRoute('employ_publication_index');
        }

        return $this->render('employ/publication/edit.html.twig', [
            'form' => $form->createView(),
            'publication' => $publication,
        ]);
    }

    #[Route('/publications/{id}/delete', name: 'employ_publication_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publicationDelete(Request $request, Publication $publication, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_publication_' . $publication->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($publication);
            $entityManager->flush();
            $this->addFlash('success', 'Publication supprimee.');
        }

        return $this->redirectToRoute('employ_publication_index');
    }

    #[Route('/commentaires/new', name: 'employ_commentaire_new', methods: ['GET', 'POST'])]
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
                return $this->redirectToRoute('employ_publication_index');
            }

            $commentaire->setDateCommentaire(new \DateTime());
            $user = $this->getUser();
            if ($user instanceof User) {
                $commentaire->setUser($user);
            }

            $entityManager->persist($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire ajoute.');

            return $this->redirectToRoute('employ_publication_index');
        }

        return $this->render('employ/commentaire/new.html.twig', [
            'form' => $form->createView(),
            'publication' => $commentaire->getPublication(),
        ]);
    }

    #[Route('/commentaires/{id}/edit', name: 'employ_commentaire_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
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

            return $this->redirectToRoute('employ_publication_index');
        }

        return $this->render('employ/commentaire/edit.html.twig', [
            'form' => $form->createView(),
            'commentaire' => $commentaire,
        ]);
    }

    #[Route('/commentaires/{id}/delete', name: 'employ_commentaire_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function commentaireDelete(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_commentaire_' . $commentaire->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire supprime.');
        }

        return $this->redirectToRoute('employ_publication_index');
    }
}
