<?php

namespace App\ReportBundle\Service;

use App\Entity\User;
use App\Entity\Conge;
use App\Repository\CongeRepository;
use App\Repository\AbsenceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReportGenerator
{
    private EntityManagerInterface $em;
    private CongeRepository $congeRepository;
    private AbsenceRepository $absenceRepository;
    private UserRepository $userRepository;
    
    public function __construct(
        EntityManagerInterface $em,
        CongeRepository $congeRepository,
        AbsenceRepository $absenceRepository,
        UserRepository $userRepository
    ) {
        $this->em = $em;
        $this->congeRepository = $congeRepository;
        $this->absenceRepository = $absenceRepository;
        $this->userRepository = $userRepository;
    }
    
    /**
     * Get statistics for a manager's team
     */
    public function getManagerStats(User $manager): array
    {
        $team = $this->userRepository->findBy(['user' => $manager, 'role' => 'EMPLOYE']);
        $teamIds = array_map(fn($e) => $e->getId(), $team);
        
        if (empty($teamIds)) {
            return [
                'team_size' => 0,
                'pending_requests' => 0,
                'approved_this_month' => 0,
                'rejected_this_month' => 0,
                'total_leave_days_this_month' => 0,
                'avg_absence_rate' => 0,
            ];
        }
        
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');
        
        // Pending requests
        $qb = $this->em->createQueryBuilder();
        $pending = $qb->select('COUNT(a.id)')
            ->from('App\Entity\Absence', 'a')
            ->join('a.user', 'u')
            ->where('u.user = :manager')
            ->andWhere('a.statut = :status')
            ->setParameter('manager', $manager)
            ->setParameter('status', 'en_attente')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Approved this month
        $qb2 = $this->em->createQueryBuilder();
        $approved = $qb2->select('COUNT(a.id)')
            ->from('App\Entity\Absence', 'a')
            ->join('a.user', 'u')
            ->where('u.user = :manager')
            ->andWhere('a.statut = :status')
            ->andWhere('a.date_debut >= :start')
            ->andWhere('a.date_debut <= :end')
            ->setParameter('manager', $manager)
            ->setParameter('status', 'approuve')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Calculate monthly leave days and rejected requests
        $totalDays = 0;
        $rejected = 0;
        foreach ($teamIds as $empId) {
            $employee = $this->userRepository->find($empId);
            $conges = $this->congeRepository->findBy(['user' => $employee]);
            foreach ($conges as $conge) {
                $absence = $conge->getAbsence();
                if (!$absence) {
                    continue;
                }

                if (!$this->overlapsMonth($absence->getDateDebut(), $absence->getDateFin(), $startOfMonth, $endOfMonth)) {
                    continue;
                }

                if ($absence->getStatut() === 'approuve') {
                    $totalDays += $this->countDaysInMonth($absence->getDateDebut(), $absence->getDateFin(), $startOfMonth, $endOfMonth);
                } elseif ($absence->getStatut() === 'refuse') {
                    $rejected++;
                }
            }
        }
        
        return [
            'team_size' => count($team),
            'pending_requests' => $pending,
            'approved_this_month' => $approved,
            'rejected_this_month' => $rejected,
            'total_leave_days_this_month' => $totalDays,
            'avg_absence_rate' => count($team) > 0 ? round($totalDays / count($team), 2) : 0,
        ];
    }
    
    /**
     * Get global statistics (for Admin RH)
     */
    public function getGlobalStats(): array
    {
        $totalEmployees = $this->userRepository->count(['role' => 'EMPLOYE']);
        $totalManagers = $this->userRepository->count(['role' => 'MANAGER']);
        
        return [
            'total_employees' => $totalEmployees,
            'total_managers' => $totalManagers,
            'total_leave_requests' => 0,
            'pending_requests' => 0,
            'approved_requests' => 0,
        ];
    }
    
    /**
     * Get employee statistics
     */
    public function getEmployeeStats(User $employee): array
    {
        $conges = $this->congeRepository->findBy(['user' => $employee]);
        
        $pending = 0;
        $approved = 0;
        $rejected = 0;
        $totalDays = 0;
        
        foreach ($conges as $conge) {
            $absence = $conge->getAbsence();
            if ($absence) {
                switch ($absence->getStatut()) {
                    case 'en_attente': $pending++; break;
                    case 'approuve': 
                        $approved++; 
                        $days = $absence->getDateDebut()->diff($absence->getDateFin())->days + 1;
                        $totalDays += $days;
                        break;
                    case 'refuse': $rejected++; break;
                }
            }
        }
        
        return [
            'pending_requests' => $pending,
            'approved_requests' => $approved,
            'rejected_requests' => $rejected,
            'total_leave_days' => $totalDays,
        ];
    }
    
    /**
     * Generate team absence report for a specific month
     */
    public function generateTeamAbsenceReport(User $manager, int $year, int $month): array
    {
        $team = $this->userRepository->findBy(['user' => $manager, 'role' => 'EMPLOYE']);
        
        $startDate = new \DateTime("$year-$month-01");
        $endDate = new \DateTime($startDate->format('Y-m-t'));
        
        $report = [];
        foreach ($team as $employee) {
            $conges = $this->congeRepository->findBy(['user' => $employee]);
            $absences = [];
            
            foreach ($conges as $conge) {
                $absence = $conge->getAbsence();
                if ($absence && 
                    $absence->getStatut() === 'approuve' &&
                    $absence->getDateDebut() <= $endDate &&
                    $absence->getDateFin() >= $startDate) {
                    $absences[] = [
                        'start' => $absence->getDateDebut()->format('Y-m-d'),
                        'end' => $absence->getDateFin()->format('Y-m-d'),
                        'days' => $absence->getDateDebut()->diff($absence->getDateFin())->days + 1,
                        'type' => 'Conge',
                    ];
                }
            }
            
            $report[] = [
                'employee' => $employee->getPrenom() . ' ' . $employee->getNom(),
                'email' => $employee->getEmail(),
                'absences' => $absences,
                'total_days' => array_sum(array_column($absences, 'days')),
            ];
        }
        
        return $report;
    }
    
    /**
     * Get monthly summary
     */
    public function getMonthlySummary(int $year, int $month): array
    {
        $startDate = new \DateTime("$year-$month-01");
        $endDate = new \DateTime($startDate->format('Y-m-t'));

        $qb = $this->em->createQueryBuilder();
        $conges = $qb->select('c', 'a', 'u')
            ->from(Conge::class, 'c')
            ->innerJoin('c.absence', 'a')
            ->leftJoin('c.user', 'u')
            ->andWhere('a.date_debut <= :endDate')
            ->andWhere('a.date_fin >= :startDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        $totalRequests = 0;
        $approved = 0;
        $rejected = 0;
        $pending = 0;

        foreach ($conges as $conge) {
            $absence = $conge->getAbsence();
            if (!$absence) {
                continue;
            }

            $totalRequests++;

            switch ($absence->getStatut()) {
                case 'approuve':
                    $approved++;
                    break;
                case 'refuse':
                    $rejected++;
                    break;
                default:
                    $pending++;
                    break;
            }
        }
        
        return [
            'year' => $year,
            'month' => $month,
            'total_requests' => $totalRequests,
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $pending,
        ];
    }

    private function overlapsMonth(\DateTimeInterface $start, \DateTimeInterface $end, \DateTimeInterface $monthStart, \DateTimeInterface $monthEnd): bool
    {
        return $start <= $monthEnd && $end >= $monthStart;
    }

    private function countDaysInMonth(\DateTimeInterface $start, \DateTimeInterface $end, \DateTimeInterface $monthStart, \DateTimeInterface $monthEnd): int
    {
        $effectiveStart = $start > $monthStart ? $start : $monthStart;
        $effectiveEnd = $end < $monthEnd ? $end : $monthEnd;

        if ($effectiveEnd < $effectiveStart) {
            return 0;
        }

        return $effectiveStart->diff($effectiveEnd)->days + 1;
    }
    
    /**
     * Get team leave balance for a manager
     */
    public function getTeamLeaveBalance(User $manager): array
    {
        $team = $this->userRepository->findBy(['user' => $manager, 'role' => 'EMPLOYE']);
        
        $balances = [];
        foreach ($team as $employee) {
            $conges = $this->congeRepository->findBy(['user' => $employee]);
            
            $used = 0;
            $pending = 0;
            $approved = 0;
            
            foreach ($conges as $conge) {
                $absence = $conge->getAbsence();
                if ($absence) {
                    $days = $absence->getDateDebut()->diff($absence->getDateFin())->days + 1;
                    
                    switch ($absence->getStatut()) {
                        case 'approuve':
                            $used += $days;
                            $approved++;
                            break;
                        case 'en_attente':
                            $pending++;
                            break;
                    }
                }
            }
            
            $balances[] = [
                'employee' => $employee->getPrenom() . ' ' . $employee->getNom(),
                'total' => 30, // Standard annual leave
                'used' => $used,
                'remaining' => 30 - $used,
                'pending' => $pending,
            ];
        }
        
        return $balances;
    }
    
    /**
     * Get all leave balances (for Admin RH)
     */
    public function getAllLeaveBalances(): array
    {
        $employees = $this->userRepository->findBy(['role' => 'EMPLOYE']);
        
        $balances = [];
        foreach ($employees as $employee) {
            $conges = $this->congeRepository->findBy(['user' => $employee]);
            
            $used = 0;
            $pending = 0;
            
            foreach ($conges as $conge) {
                $absence = $conge->getAbsence();
                if ($absence) {
                    $days = $absence->getDateDebut()->diff($absence->getDateFin())->days + 1;
                    
                    if ($absence->getStatut() === 'approuve') {
                        $used += $days;
                    } elseif ($absence->getStatut() === 'en_attente') {
                        $pending++;
                    }
                }
            }
            
            $balances[] = [
                'employee' => $employee->getPrenom() . ' ' . $employee->getNom(),
                'total' => 30,
                'used' => $used,
                'remaining' => 30 - $used,
                'pending' => $pending,
            ];
        }
        
        return $balances;
    }
    
    /**
     * Get leave balance for a single employee
     */
    public function getEmployeeLeaveBalance(User $employee): array
    {
        $conges = $this->congeRepository->findBy(['user' => $employee]);
        
        $used = 0;
        $pending = 0;
        
        foreach ($conges as $conge) {
            $absence = $conge->getAbsence();
            if ($absence) {
                $days = $absence->getDateDebut()->diff($absence->getDateFin())->days + 1;
                
                if ($absence->getStatut() === 'approuve') {
                    $used += $days;
                } elseif ($absence->getStatut() === 'en_attente') {
                    $pending++;
                }
            }
        }
        
        return [
            'employee' => $employee->getPrenom() . ' ' . $employee->getNom(),
            'total' => 30,
            'used' => $used,
            'remaining' => 30 - $used,
            'pending' => $pending,
        ];
    }
    
    /**
     * Generate CSV export
     */
    public function generateCsvExport(string $type, User $user): string
    {
        $csv = "Date,Employé,Type,Statut,Jours\n";
        
        if ($type === 'absences') {
            if ($user->getRole() === 'MANAGER') {
                $team = $this->userRepository->findBy(['user' => $user]);
                foreach ($team as $emp) {
                    $conges = $this->congeRepository->findBy(['user' => $emp]);
                    foreach ($conges as $conge) {
                        $absence = $conge->getAbsence();
                        if ($absence) {
                            $days = $absence->getDateDebut()->diff($absence->getDateFin())->days + 1;
                            $csv .= sprintf("%s,%s,%s,%s,%d\n",
                                $absence->getDateDebut()->format('Y-m-d'),
                                $emp->getPrenom() . ' ' . $emp->getNom(),
                                'Conge',
                                $absence->getStatut(),
                                $days
                            );
                        }
                    }
                }
            }
        }
        
        return $csv;
    }
}
