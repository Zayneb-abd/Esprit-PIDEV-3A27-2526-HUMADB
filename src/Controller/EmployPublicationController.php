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

#[Route('/employ/publication')]
#[IsGranted('ROLE_EMPLOY')]
class EmployPublicationController extends AbstractController
{
    private $reactionRepository;
    private $entityManager;
    private $paginator;

    public function __construct(ReactionPublicationRepository $reactionRepository, EntityManagerInterface $entityManager, PaginatorInterface $paginator)
    {
        $this->reactionRepository = $reactionRepository;
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
    }

    /**
     * Afficher la liste des publications avec les réactions
     */
    #[Route('/', name: 'employ_publication_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Paginer avec findBy simple
        $page = $request->query->getInt('page', 1);
        $limit = 5; // 5 publications par page
        $offset = ($page - 1) * $limit;
        
        // Récupérer toutes les publications triées par ID DESC
        $allPublications = $this->entityManager->getRepository(Publication::class)
            ->findBy([], ['id' => 'DESC']);
        
        // Extraire seulement les publications pour la page actuelle
        $publications = array_slice($allPublications, $offset, $limit);
        
        // Debug simple
        dump([
            'page' => $page,
            'offset' => $offset,
            'limit' => $limit,
            'total_publications' => count($allPublications),
            'count_publications_page' => count($publications),
            'ids_page' => array_map(fn($p) => $p->getId(), $publications)
        ]);

        
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
            return $this->redirectToRoute('employ_publication_index');
        }

        $commentaire = new Commentaire();
        $commentaire->setContenu($contenu);
        $commentaire->setDateCommentaire(new \DateTime());
        $commentaire->setPublication($publication);
        $commentaire->setUser($user);

        $this->entityManager->persist($commentaire);
        $this->entityManager->flush();

        return $this->redirectToRoute('employ_publication_index');
    }

    /**
     * Supprimer un commentaire
     */
    #[Route('/comment/{id}/delete', name: 'employ_comment_delete', methods: ['POST'])]
    public function deleteComment(Commentaire $commentaire): Response
    {
        $user = $this->getUser();
        if (!$user || $commentaire->getUser() !== $user) {
            return $this->redirectToRoute('employ_publication_index');
        }

        $this->entityManager->remove($commentaire);
        $this->entityManager->flush();

        return $this->redirectToRoute('employ_publication_index');
    }
}
