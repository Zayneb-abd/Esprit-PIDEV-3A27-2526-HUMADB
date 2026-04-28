<?php

namespace App\Controller;

use App\Entity\OffreEmploi;
use App\Repository\OffreEmploiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/')]
class ClientController extends AbstractController
{
    #[Route('/', name: 'client_home')]
    public function home(): Response
    {
        return $this->render('client/home/index.html.twig');
    }

    #[Route('/about', name: 'client_about')]
    public function about(): Response
    {
        return $this->render('client/about/index.html.twig');
    }

    #[Route('/services', name: 'client_services')]
    public function services(): Response
    {
        return $this->render('client/service/index.html.twig');
    }

    #[Route('/contact', name: 'contact_index')]
    public function contact(): Response
    {
        return $this->render('client/contact/index.html.twig');
    }

    #[Route('/blog', name: 'client_blog')]
    public function blog(): Response
    {
        return $this->render('client/blog/blog.html.twig');
    }

    #[Route('/blog/detail', name: 'client_blog_detail')]
    public function blogDetail(): Response
    {
        return $this->render('client/blog/detail.html.twig');
    }

    #[Route('/features', name: 'client_features')]
    public function features(): Response
    {
        return $this->render('client/pages/features.html.twig');
    }

    #[Route('/team', name: 'client_team')]
    public function team(): Response
    {
        return $this->render('client/pages/team.html.twig');
    }

    #[Route('/testimonial', name: 'client_testimonial')]
    public function testimonial(): Response
    {
        return $this->render('client/pages/testimonial.html.twig');
    }

    #[Route('/price', name: 'client_price')]
    public function price(): Response
    {
        return $this->render('client/pages/price.html.twig');
    }

    #[Route('/quote', name: 'client_quote')]
    public function quote(): Response
    {
        return $this->render('client/pages/quote.html.twig');
    }

    #[Route('/jobs', name: 'client_jobs')]
    public function jobs(OffreEmploiRepository $offreEmploiRepository): Response
    {
        return $this->render('client/jobs/index.html.twig', [
            'offres' => $offreEmploiRepository->findBy([], ['date_publication' => 'DESC', 'id' => 'DESC']),
        ]);
    }

    #[Route('/jobs/{id}', name: 'client_job_show', requirements: ['id' => '\d+'])]
    public function showJob(OffreEmploi $offreEmploi): Response
    {
        return $this->render('client/jobs/show.html.twig', [
            'offre' => $offreEmploi,
        ]);
    }
}
