<?php

namespace App\PlanningBundle\Service;

use App\Repository\CongeRepository;
use App\Repository\AbsenceRepository;
use App\Entity\User;

class CalendarService
{
    private CongeRepository $congeRepository;
    private AbsenceRepository $absenceRepository;
    
    public function __construct(
        CongeRepository $congeRepository,
        AbsenceRepository $absenceRepository
    ) {
        $this->congeRepository = $congeRepository;
        $this->absenceRepository = $absenceRepository;
    }
    
    /**
     * Get calendar events for a user based on their role
     */
    public function getEventsForUser(User $user, ?array $teamMembers = null): array
    {
        $events = [];
        
        if ($user->getRole() === 'MANAGER' && $teamMembers) {
            $events = $this->getTeamEvents($teamMembers);
        } elseif ($user->getRole() === 'ADMIN_RH') {
            $events = $this->getAllEvents();
        } else {
            $events = $this->getPersonalEvents($user);
        }
        
        return $this->formatForCalendar($events);
    }
    
    /**
     * Get available leave balance for display
     */
    public function getLeaveBalance(User $user): array
    {
        $conges = $this->congeRepository->findBy([
            'user' => $user,
        ]);
        
        $approvedDays = 0;
        $pendingDays = 0;
        
        foreach ($conges as $conge) {
            $absence = $conge->getAbsence();
            $days = $this->calculateWorkingDays(
                $absence->getDateDebut(),
                $absence->getDateFin()
            );
            
            if ($absence->getStatut() === 'approuve') {
                $approvedDays += $days;
            } elseif ($absence->getStatut() === 'en_attente') {
                $pendingDays += $days;
            }
        }
        
        // Default annual leave: 25 days (can be configured per company)
        $totalAllowed = 25;
        $remaining = $totalAllowed - $approvedDays;
        
        return [
            'total' => $totalAllowed,
            'used' => $approvedDays,
            'pending' => $pendingDays,
            'remaining' => max(0, $remaining),
            'percentage_used' => round(($approvedDays / $totalAllowed) * 100, 1),
        ];
    }
    
    private function getTeamEvents(array $teamMembers): array
    {
        $teamIds = array_map(fn($m) => $m->getId(), $teamMembers);
        
        $conges = $this->congeRepository->findBy(['user' => $teamIds]);
        $absences = $this->absenceRepository->findBy(['user' => $teamIds]);
        
        return ['conges' => $conges, 'absences' => $absences];
    }
    
    private function getAllEvents(): array
    {
        $conges = $this->congeRepository->findAll();
        $absences = $this->absenceRepository->findAll();
        
        return ['conges' => $conges, 'absences' => $absences];
    }
    
    private function getPersonalEvents(User $user): array
    {
        $conges = $this->congeRepository->findBy(['user' => $user]);
        $absences = $this->absenceRepository->findBy(['user' => $user]);
        
        return ['conges' => $conges, 'absences' => $absences];
    }
    
    private function formatForCalendar(array $data): array
    {
        $events = [];
        
        foreach ($data['conges'] ?? [] as $conge) {
            $absence = $conge->getAbsence();
            $events[] = [
                'id' => 'conge_' . $conge->getId(),
                'title' => $this->formatEventTitle($conge->getUser(), 'conge'),
                'start' => $absence->getDateDebut()->format('Y-m-d'),
                'end' => $absence->getDateFin()->modify('+1 day')->format('Y-m-d'),
                'color' => $this->getStatusColor($absence->getStatut(), 'conge'),
                'extendedProps' => [
                    'type' => 'conge',
                    'user' => $conge->getUser()->getPrenom() . ' ' . $conge->getUser()->getNom(),
                    'status' => $absence->getStatut(),
                ],
            ];
        }
        
        foreach ($data['absences'] ?? [] as $absence) {
            $events[] = [
                'id' => 'absence_' . $absence->getId(),
                'title' => $this->formatEventTitle($absence->getUser(), 'absence'),
                'start' => $absence->getDateDebut()->format('Y-m-d'),
                'end' => $absence->getDateFin()->modify('+1 day')->format('Y-m-d'),
                'color' => $this->getStatusColor($absence->getStatut(), 'absence'),
                'extendedProps' => [
                    'type' => 'absence',
                    'user' => $absence->getUser()->getPrenom() . ' ' . $absence->getUser()->getNom(),
                    'status' => $absence->getStatut(),
                ],
            ];
        }
        
        return $events;
    }
    
    private function formatEventTitle($user, string $type): string
    {
        $name = $user->getPrenom();
        $typeLabel = $type === 'conge' ? 'Congé' : 'Absence';
        return sprintf('%s (%s)', $name, $typeLabel);
    }
    
    private function getStatusColor(string $status, string $type): string
    {
        $colors = [
            'conge' => [
                'approuve' => '#28a745',
                'refuse' => '#dc3545',
                'en_attente' => '#ffc107',
            ],
            'absence' => [
                'approuve' => '#fd7e14',
                'refuse' => '#dc3545',
                'en_attente' => '#6f42c1',
            ],
        ];
        
        return $colors[$type][$status] ?? '#6c757d';
    }
    
    private function calculateWorkingDays(\DateTime $start, \DateTime $end): int
    {
        $days = 0;
        $current = clone $start;
        $end = clone $end;
        
        while ($current <= $end) {
            $dayOfWeek = (int)$current->format('N');
            if ($dayOfWeek < 6) { // Monday to Friday
                $days++;
            }
            $current->modify('+1 day');
        }
        
        return $days;
    }
}
