<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\Commentaire;
use App\Repository\ReactionPublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employ/publication-legacy')]
#[IsGranted('ROLE_EMPLOY')]
class EmployPublicationController extends AbstractController
{
    private $reactionRepository;
    private $entityManager;

    public function __construct(ReactionPublicationRepository $reactionRepository, EntityManagerInterface $entityManager)
    {
        $this->reactionRepository = $reactionRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Afficher la liste des publications avec les réactions
     */
    #[Route('/', name: 'employ_publication_legacy_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $publications = $this->entityManager->getRepository(Publication::class)
            ->findBy([], ['datePublication' => 'DESC']);

        $page = max(1, (int) $request->query->get('page', 1));
        $publications = $paginator->paginate($publications, $page, 6);

        return $this->render('employ/publication/index.html.twig', [
            'publications' => $publications,
            'reaction_repository' => $this->reactionRepository
        ]);
    }

    /**
     * Ajouter un commentaire à une publication
     */
    #[Route('/{id}/comment', name: 'employ_publication_comment', methods: ['POST'])]
    public function addComment(Request $request, Publication $publication): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $contenu = $request->request->get('contenu');
        if (empty(trim($contenu))) {
            return $this->redirectToRoute('employ_publication_legacy_index');
        }

        $commentaire = new Commentaire();
        $commentaire->setContenu($contenu);
        $commentaire->setDateCommentaire(new \DateTime());
        $commentaire->setPublication($publication);
        $commentaire->setUser($user);

        $this->entityManager->persist($commentaire);
        $this->entityManager->flush();

        return $this->redirectToRoute('employ_publication_legacy_index');
    }

    /**
     * Supprimer un commentaire
     */
    #[Route('/comment/{id}/delete', name: 'employ_comment_delete', methods: ['POST'])]
    public function deleteComment(Commentaire $commentaire): Response
    {
        $user = $this->getUser();
        if (!$user || $commentaire->getUser() !== $user) {
            return $this->redirectToRoute('employ_publication_legacy_index');
        }

        $this->entityManager->remove($commentaire);
        $this->entityManager->flush();

        return $this->redirectToRoute('employ_publication_legacy_index');
    }
}
