<?php

namespace App\PlanningBundle\Controller;

use App\Entity\Conge;
use App\Entity\Absence;
use App\Repository\CongeRepository;
use App\Repository\AbsenceRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/planning')]
class PlanningController extends AbstractController
{
    #[Route('/calendar', name: 'planning_calendar')]
    public function calendar(
        CongeRepository $congeRepository,
        AbsenceRepository $absenceRepository,
        UserRepository $userRepository
    ): Response {
        $user = $this->getUser();
        $year = date('Y');
        $month = date('n');
        
        // Get events based on user role
        if ($user->getRole() === 'MANAGER') {
            // Manager sees their team's events
            $teamMembers = $userRepository->findBy(['user' => $user, 'role' => 'EMPLOYE']);
            $teamIds = array_map(fn($e) => $e->getId(), $teamMembers);
            $conges = $congeRepository->findBy(['user' => $teamIds]);
            $absences = $absenceRepository->findBy(['user' => $teamIds]);
        } elseif ($user->getRole() === 'ADMIN_RH') {
            // Admin sees all
            $conges = $congeRepository->findAll();
            $absences = $absenceRepository->findAll();
        } else {
            // Employee sees only their own
            $conges = $congeRepository->findBy(['user' => $user]);
            $absences = $absenceRepository->findBy(['user' => $user]);
        }
        
        // Convert to calendar events
        $events = $this->convertToEvents($conges, $absences);
        
        // Calculate conflict risks
        $conflicts = $this->detectConflicts($events, $teamMembers ?? []);
        
        // Generate calendar days for the current month
        $calendarDays = $this->generateCalendarDays($year, $month, $events);
        
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        return $this->render('@Planning/calendar/simple.html.twig', [
            'events' => $events,
            'conflicts' => $conflicts,
            'year' => $year,
            'month' => $month,
            'current_month_name' => $monthNames[$month],
            'current_year' => $year,
            'calendar_days' => $calendarDays,
            'teamMembers' => $teamMembers ?? [],
        ]);
    }
    
    #[Route('/api/events', name: 'planning_api_events')]
    public function apiEvents(
        Request $request,
        CongeRepository $congeRepository,
        AbsenceRepository $absenceRepository
    ): Response {
        $user = $this->getUser();
        $start = $request->query->get('start');
        $end = $request->query->get('end');
        
        // Fetch events for date range
        $events = [];
        
        return $this->json($events);
    }
    
    private function convertToEvents(array $conges, array $absences): array
    {
        $events = [];
        
        foreach ($conges as $conge) {
            $absence = $conge->getAbsence();
            $events[] = [
                'id' => 'conge_' . $conge->getId(),
                'title' => $conge->getUser()->getPrenom() . ' ' . $conge->getUser()->getNom(),
                'start' => $absence->getDateDebut()->format('Y-m-d'),
                'end' => $absence->getDateFin()->format('Y-m-d'),
                'type' => 'conge',
                'status' => $absence->getStatut(),
                'user' => $conge->getUser()->getPrenom() . ' ' . $conge->getUser()->getNom(),
                'color' => $this->getEventColor('conge', $absence->getStatut()),
            ];
        }
        
        foreach ($absences as $absence) {
            $events[] = [
                'id' => 'absence_' . $absence->getId(),
                'title' => $absence->getUser()->getPrenom() . ' ' . $absence->getUser()->getNom(),
                'start' => $absence->getDateDebut()->format('Y-m-d'),
                'end' => $absence->getDateFin()->format('Y-m-d'),
                'type' => 'absence',
                'status' => $absence->getStatut(),
                'user' => $absence->getUser()->getPrenom() . ' ' . $absence->getUser()->getNom(),
                'color' => $this->getEventColor('absence', $absence->getStatut()),
            ];
        }
        
        return $events;
    }
    
    private function getEventColor(string $type, string $status): string
    {
        if ($type === 'conge') {
            return match($status) {
                'approuve' => '#28a745',  // Green
                'refuse' => '#dc3545',     // Red
                default => '#ffc107',      // Yellow (en_attente)
            };
        }
        
        return match($status) {
            'approuve' => '#fd7e14',     // Orange
            'refuse' => '#dc3545',       // Red
            default => '#6f42c1',        // Purple (en_attente)
        };
    }
    
    private function detectConflicts(array $events, array $teamMembers): array
    {
        $conflicts = [];
        $teamSize = count($teamMembers);
        
        if ($teamSize === 0) {
            return $conflicts;
        }
        
        // Group events by date
        $eventsByDate = [];
        foreach ($events as $event) {
            $start = new \DateTime($event['start']);
            $end = new \DateTime($event['end']);
            
            while ($start <= $end) {
                $dateKey = $start->format('Y-m-d');
                if (!isset($eventsByDate[$dateKey])) {
                    $eventsByDate[$dateKey] = [];
                }
                $eventsByDate[$dateKey][] = $event;
                $start->modify('+1 day');
            }
        }
        
        // Check each date for conflicts (>30% of team absent)
        $threshold = ceil($teamSize * 0.3);
        
        foreach ($eventsByDate as $date => $dayEvents) {
            $absentCount = count($dayEvents);
            if ($absentCount > $threshold) {
                $conflicts[] = [
                    'date' => $date,
                    'absent_count' => $absentCount,
                    'team_size' => $teamSize,
                    'percentage' => round(($absentCount / $teamSize) * 100, 1),
                    'severity' => $absentCount > ($teamSize * 0.5) ? 'high' : 'medium',
                ];
            }
        }
        
        return $conflicts;
    }
    
    private function generateCalendarDays(int $year, int $month, array $events): array
    {
        $firstDay = new \DateTime("$year-$month-01");
        $lastDay = new \DateTime($firstDay->format('Y-m-t'));
        $startDayOfWeek = (int)$firstDay->format('N'); // 1 = Monday
        
        $calendar = [];
        $week = [];
        $dayCount = 0;
        
        // Fill empty days at start
        for ($i = 1; $i < $startDayOfWeek; $i++) {
            $week[] = ['day' => null, 'events' => [], 'is_weekend' => false, 'is_today' => false];
            $dayCount++;
        }
        
        // Fill days of month
        $currentDay = clone $firstDay;
        $today = new \DateTime();
        
        while ($currentDay <= $lastDay) {
            $dayNum = (int)$currentDay->format('j');
            $dayOfWeek = (int)$currentDay->format('N');
            $isWeekend = ($dayOfWeek >= 6);
            $isToday = ($currentDay->format('Y-m-d') === $today->format('Y-m-d'));
            
            // Find events for this day
            $dayEvents = [];
            $dateStr = $currentDay->format('Y-m-d');
            foreach ($events as $event) {
                if ($dateStr >= $event['start'] && $dateStr <= $event['end']) {
                    $dayEvents[] = $event;
                }
            }
            
            $week[] = [
                'day' => $dayNum,
                'events' => $dayEvents,
                'is_weekend' => $isWeekend,
                'is_today' => $isToday,
            ];
            
            $dayCount++;
            
            if ($dayCount % 7 === 0) {
                $calendar[] = $week;
                $week = [];
            }
            
            $currentDay->modify('+1 day');
        }
        
        // Fill empty days at end
        while ($dayCount % 7 !== 0) {
            $week[] = ['day' => null, 'events' => [], 'is_weekend' => false, 'is_today' => false];
            $dayCount++;
        }
        
        if (!empty($week)) {
            $calendar[] = $week;
        }
        
        return $calendar;
    }
}
