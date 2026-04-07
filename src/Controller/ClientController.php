<?php

namespace App\Controller;

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

    #[Route('/contact', name: 'client_contact')]
    public function contact(): Response
    {
        return $this->render('client/contact/index.html.twig');
    }

    #[Route('/services', name: 'client_services')]
    public function services(): Response
    {
        return $this->render('client/service/index.html.twig');
    }
}
