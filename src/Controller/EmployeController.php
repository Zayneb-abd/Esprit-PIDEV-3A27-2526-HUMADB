<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
        return $this->render('employ/product/create.html.twig');
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
}
