<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class UserApiController extends AbstractController
{
    #[Route('/me', name: 'api_me_get', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json($this->serializeUser($user));
    }

    #[Route('/me', name: 'api_me_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateMe(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON body'], 400);
        }

        if (array_key_exists('nom', $payload)) {
            $user->setNom((string) $payload['nom']);
        }
        if (array_key_exists('prenom', $payload)) {
            $user->setPrenom((string) $payload['prenom']);
        }
        if (array_key_exists('date_naissance', $payload)) {
            $value = $payload['date_naissance'];
            if ($value === null || $value === '') {
                $user->setDate_naissance(null);
            } else {
                $date = \DateTime::createFromFormat('Y-m-d', (string) $value);
                if (!$date) {
                    return $this->json(['error' => 'date_naissance must be YYYY-MM-DD'], 400);
                }
                $user->setDate_naissance($date);
            }
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Profile updated successfully',
            'user' => $this->serializeUser($user),
        ]);
    }

    #[Route('/admin/users/{id}/status', name: 'api_admin_user_status', methods: ['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateAccountStatus(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $targetUser = $userRepository->find($id);
        if (!$targetUser instanceof User) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !array_key_exists('is_active', $payload)) {
            return $this->json(['error' => 'Field "is_active" is required'], 400);
        }

        $isActive = filter_var($payload['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($isActive === null) {
            return $this->json(['error' => '"is_active" must be boolean'], 400);
        }

        $targetUser->setIsActive($isActive);
        $entityManager->flush();

        return $this->json([
            'message' => 'Account status updated successfully',
            'user' => $this->serializeUser($targetUser),
        ]);
    }

    #[Route('/admin/users/export', name: 'api_admin_users_export', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function exportUsers(Request $request, UserRepository $userRepository): StreamedResponse
    {
        $search = trim((string) $request->query->get('q', ''));
        $role = trim((string) $request->query->get('role', ''));
        $users = $userRepository->findForExport($search, $role);

        $response = new StreamedResponse(function () use ($users): void {
            $handle = fopen('php://output', 'w');
            if (!$handle) {
                return;
            }

            fputcsv($handle, ['id', 'nom', 'prenom', 'email', 'role', 'is_active', 'reputation_score', 'date_naissance']);
            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->getId(),
                    $user->getNom(),
                    $user->getPrenom(),
                    $user->getEmail(),
                    $user->getRole(),
                    $user->isActive() ? '1' : '0',
                    $user->getReputationScore(),
                    $user->getDateNaissance() ? $user->getDateNaissance()->format('Y-m-d') : '',
                ]);
            }
            fclose($handle);
        });

        $filename = 'users_export_' . (new \DateTime())->format('Ymd_His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'is_active' => $user->isActive(),
            'reputation_score' => $user->getReputationScore(),
            'date_naissance' => $user->getDateNaissance() ? $user->getDateNaissance()->format('Y-m-d') : null,
        ];
    }
}
