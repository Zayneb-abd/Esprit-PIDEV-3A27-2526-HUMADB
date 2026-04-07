<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/condidat')]
class CondidatController extends AbstractController
{
    #[Route('/', name: 'condidat_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('condidat/dashboard/index.html.twig');
    }

    #[Route('/inventory', name: 'condidat_inventory')]
    public function inventory(): Response
    {
        return $this->render('condidat/inventory/index.html.twig');
    }

    #[Route('/product/create', name: 'condidat_product_create')]
    public function createProduct(): Response
    {
        return $this->render('condidat/product/create.html.twig');
    }

    #[Route('/reports', name: 'condidat_reports')]
    public function reports(): Response
    {
        return $this->render('condidat/reports/index.html.twig');
    }

    #[Route('/docs', name: 'condidat_docs')]
    public function docs(): Response
    {
        return $this->render('condidat/docs/index.html.twig');
    }
}
