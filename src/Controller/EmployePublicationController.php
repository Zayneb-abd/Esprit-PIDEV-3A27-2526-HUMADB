<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\Commentaire;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CommentValidatorService;

#[Route('/employ/publication')]
#[IsGranted('ROLE_EMPLOYE')]
class EmployePublicationController extends AbstractController
{
    #[Route('/', name: 'employ_publication_index', methods: ['GET'])]
    public function index(Request $request, PublicationRepository $publicationRepository, PaginatorInterface $paginator): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 6;

        $publications = $paginator->paginate(
            $publicationRepository->findBy([], ['date_publication' => 'DESC']),
            $page,
            $limit
        );

        return $this->render('employ/publication/index.html.twig', [
            'publications' => $publications,
        ]);
    }

    #[Route('/{id}/comment', name: 'employ_publication_comment', methods: ['POST'])]
    public function addComment(Publication $publication, Request $request, EntityManagerInterface $em, CommentValidatorService $commentValidator): Response
    {
        $contenu = $request->request->get('contenu');
        
        if (empty($contenu)) {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('employ_publication_index');
        }

        // Valider le commentaire avec BanBuilder
        $validation = $commentValidator->validateComment($contenu);

        if (!$validation['is_valid']) {
            // Si des mots interdits sont détectés
            if ($validation['is_censored']) {
                $this->addFlash('error', 'Commentaire rejeté : contient des mots inappropriés : ' . implode(', ', $validation['bad_words_found']));
                return $this->redirectToRoute('employ_publication_index');
            }
        }

        $commentaire = new Commentaire();
        $commentaire->setContenu($contenu);
        $commentaire->setDate_commentaire(new \DateTime());
        $commentaire->setPublication($publication);
        $commentaire->setUser($this->getUser());

        $em->persist($commentaire);
        $em->flush();

        $this->addFlash('success', 'Commentaire ajouté avec succès.');
        return $this->redirectToRoute('employ_publication_index');
    }

    #[Route('/comment/{id}/delete', name: 'employ_comment_delete', methods: ['POST'])]
    public function deleteComment(Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        // Only allow user to delete their own comments
        if ($commentaire->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce commentaire.');
            return $this->redirectToRoute('employ_publication_index');
        }

        $em->remove($commentaire);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé avec succès.');
        return $this->redirectToRoute('employ_publication_index');
    }
}
