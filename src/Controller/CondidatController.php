<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/candidat')]
class CondidatController extends AbstractController
{
    #[Route('', name: 'candidat_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('candidat/dashboard/index.html.twig');
    }

    #[Route('/inventory', name: 'candidat_inventory')]
    public function inventory(): Response
    {
        return $this->render('candidat/inventory/index.html.twig');
    }

    #[Route('/product/create', name: 'candidat_product_create')]
    public function createProduct(): Response
    {
        return $this->render('candidat/product/create.html.twig');
    }

    #[Route('/reports', name: 'candidat_reports')]
    public function reports(): Response
    {
        return $this->render('candidat/reports/index.html.twig');
    }

    #[Route('/docs', name: 'candidat_docs')]
    public function docs(): Response
    {
        return $this->render('candidat/docs/index.html.twig');
    }
}
