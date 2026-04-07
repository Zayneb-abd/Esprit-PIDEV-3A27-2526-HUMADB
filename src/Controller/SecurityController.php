<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

use App\Entity\User;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, #[CurrentUser] ?User $user = null): Response
    {
        if ($user instanceof User) {
            foreach ($user->getRoles() as $role) {
                if ($role === 'ROLE_ADMIN') {
                    return $this->redirectToRoute('admin_dashboard');
                }

                if ($role === 'ROLE_EMPLOYE') {
                    return $this->redirectToRoute('employ_dashboard');
                }

                if (in_array($role, ['ROLE_CANDIDAT', 'ROLE_CONDIDAT'], true)) {
                    return $this->redirectToRoute('candidat_dashboard');
                }
            }

            return $this->redirectToRoute('client_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method is intercepted by the firewall logout handler.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(): Response
    {
        return $this->render('security/register.html.twig');
    }
}
