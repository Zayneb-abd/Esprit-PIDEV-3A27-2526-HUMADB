<?php

namespace App\PlanningBundle\Service;

use App\PlanningBundle\Entity\PlanningRule;
use App\Repository\CongeRepository;
use App\Repository\AbsenceRepository;

class ConflictDetector
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
     * Detect conflicts for a specific date range and team
     */
    public function detectConflicts(array $teamMembers, \DateTime $start, \DateTime $end): array
    {
        $conflicts = [];
        $teamSize = count($teamMembers);
        
        if ($teamSize === 0) {
            return $conflicts;
        }
        
        $teamIds = array_map(fn($m) => $m->getId(), $teamMembers);
        
        // Get all conges and absences for the period
        $conges = $this->congeRepository->findByDateRangeAndUsers($start, $end, $teamIds);
        $absences = $this->absenceRepository->findByDateRangeAndUsers($start, $end, $teamIds);
        
        // Group by date
        $absencesByDate = $this->groupByDate($conges, $absences);
        
        // Check rules
        foreach ($absencesByDate as $date => $count) {
            $percentage = ($count / $teamSize) * 100;
            
            if ($percentage > 50) {
                $conflicts[] = [
                    'date' => $date,
                    'type' => 'critical',
                    'message' => sprintf(
                        '⚠️ %d/%d employés absents (%.0f%%) - Risque critique',
                        $count,
                        $teamSize,
                        $percentage
                    ),
                    'suggestion' => 'Reporter certaines demandes',
                ];
            } elseif ($percentage > 30) {
                $conflicts[] = [
                    'date' => $date,
                    'type' => 'warning',
                    'message' => sprintf(
                        '⚡ %d/%d employés absents (%.0f%%) - Seuil dépassé',
                        $count,
                        $teamSize,
                        $percentage
                    ),
                    'suggestion' => 'Vérifier la couverture minimum',
                ];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Check if a new request would create a conflict
     */
    public function validateNewRequest(
        $requester,
        array $teamMembers,
        \DateTime $start,
        \DateTime $end
    ): array {
        $warnings = [];
        $teamSize = count($teamMembers);
        
        if ($teamSize === 0) {
            return ['valid' => true, 'warnings' => []];
        }
        
        // Simulate adding this request
        $teamIds = array_map(fn($m) => $m->getId(), $teamMembers);
        $existing = $this->countAbsencesForPeriod($start, $end, $teamIds);
        
        foreach ($existing as $date => $count) {
            $newCount = $count + 1;
            $percentage = ($newCount / $teamSize) * 100;
            
            if ($percentage > 30) {
                $warnings[] = [
                    'date' => $date,
                    'current_absences' => $count,
                    'would_be' => $newCount,
                    'percentage' => round($percentage, 1),
                ];
            }
        }
        
        return [
            'valid' => empty($warnings) || $this->isBelowHardLimit($teamSize, $existing),
            'warnings' => $warnings,
        ];
    }
    
    private function groupByDate(array $conges, array $absences): array
    {
        $byDate = [];
        
        foreach ($conges as $conge) {
            $absence = $conge->getAbsence();
            $this->addToDateRange($byDate, $absence->getDateDebut(), $absence->getDateFin());
        }
        
        foreach ($absences as $absence) {
            $this->addToDateRange($byDate, $absence->getDateDebut(), $absence->getDateFin());
        }
        
        return $byDate;
    }
    
    private function addToDateRange(array &$byDate, \DateTime $start, \DateTime $end): void
    {
        $current = clone $start;
        $end = clone $end;
        
        while ($current <= $end) {
            $key = $current->format('Y-m-d');
            $byDate[$key] = ($byDate[$key] ?? 0) + 1;
            $current->modify('+1 day');
        }
    }
    
    private function countAbsencesForPeriod(\DateTime $start, \DateTime $end, array $userIds): array
    {
        $conges = $this->congeRepository->findByDateRangeAndUsers($start, $end, $userIds);
        $absences = $this->absenceRepository->findByDateRangeAndUsers($start, $end, $userIds);
        
        return $this->groupByDate($conges, $absences);
    }
    
    private function isBelowHardLimit(int $teamSize, array $existing): bool
    {
        $hardLimit = ceil($teamSize * 0.5); // Max 50%
        
        foreach ($existing as $count) {
            if ($count >= $hardLimit) {
                return false;
            }
        }
        
        return true;
    }
}
