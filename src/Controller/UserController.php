<?php

namespace App\Controller;

use App\Entity\Log;
use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\EditProfileType;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserController extends AbstractController
{
    #[Route('/signup', name: 'app_signup', methods: ['GET', 'POST'])]
    public function signup(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('user_profile');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check email uniqueness
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existing) {
                $this->addFlash('danger', 'Cet email est déjà utilisé.');
                return $this->render('user/signup.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Hash password
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setMdp($passwordHasher->hashPassword($user, $plainPassword));

            // Default role
            $user->setRole('EMPLOYE');

            // Handle face_image upload
            $faceImageFile = $form->get('faceImageFile')->getData();
            if ($faceImageFile) {
                $originalFilename = pathinfo($faceImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $faceImageFile->guessExtension();

                try {
                    $faceImageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/faces',
                        $newFilename
                    );
                    $user->setFaceImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('warning', 'Erreur lors du téléchargement de l\'image.');
                }
            }

            $user->setReputationScore(0);

            $em->persist($user);
            $em->flush();

            // Log
            $log = new Log();
            $log->setUser($user);
            $log->setAction('user_created');
            $em->persist($log);
            $em->flush();

            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('user/signup.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/profile', name: 'user_profile')]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/edit', name: 'user_edit_profile', methods: ['GET', 'POST'])]
    public function editProfile(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(EditProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle face_image upload
            $faceImageFile = $form->get('faceImageFile')->getData();
            if ($faceImageFile) {
                $originalFilename = pathinfo($faceImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $faceImageFile->guessExtension();

                try {
                    $faceImageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/faces',
                        $newFilename
                    );
                    $user->setFaceImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('warning', 'Erreur lors du téléchargement de l\'image.');
                }
            }

            $em->flush();

            // Log
            $log = new Log();
            $log->setUser($user);
            $log->setAction('user_updated');
            $em->persist($log);
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/edit_profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/change-password', name: 'user_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $form->get('oldPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
                $this->addFlash('danger', 'L\'ancien mot de passe est incorrect.');
                return $this->render('user/change_password.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $newPassword = $form->get('newPassword')->getData();
            $user->setMdp($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();

            // Log
            $log = new Log();
            $log->setUser($user);
            $log->setAction('password_changed');
            $em->persist($log);
            $em->flush();

            $this->addFlash('success', 'Mot de passe modifié avec succès.');
            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email', '');
            $user = $userRepository->findOneByEmail($email);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->setReset_token($token);
                $user->setToken_expiry(new \DateTime('+1 hour'));
                $em->flush();

                $resetUrl = $urlGenerator->generate('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                $emailMessage = (new Email())
                    ->from('noreply@humadb.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe — HUMADB')
                    ->html(sprintf(
                        '<p>Bonjour %s,</p><p>Cliquez ici pour réinitialiser votre mot de passe :</p><p><a href="%s">%s</a></p><p>Ce lien expire dans 1 heure.</p>',
                        htmlspecialchars($user->getPrenom()),
                        $resetUrl,
                        $resetUrl
                    ));

                try {
                    $mailer->send($emailMessage);
                } catch (\Exception $e) {
                    // Silently fail – don't reveal email existence
                }
            }

            // Always show success to avoid email enumeration
            $this->addFlash('success', 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/forgot_password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $userRepository->findOneByResetToken($token);

        if (!$user || !$user->getToken_expiry() || $user->getToken_expiry() < new \DateTime()) {
            $this->addFlash('danger', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            if (strlen($newPassword) < 8 || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
                $this->addFlash('danger', 'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.');
                return $this->render('user/reset_password.html.twig', ['token' => $token]);
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
                return $this->render('user/reset_password.html.twig', ['token' => $token]);
            }

            $user->setMdp($passwordHasher->hashPassword($user, $newPassword));
            $user->setReset_token(null);
            $user->setToken_expiry(null);
            $em->flush();

            // Log
            $log = new Log();
            $log->setUser($user);
            $log->setAction('password_changed');
            $em->persist($log);
            $em->flush();

            $this->addFlash('success', 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/reset_password.html.twig', [
            'token' => $token,
        ]);
    }
}
