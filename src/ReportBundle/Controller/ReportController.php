<?php

namespace App\ReportBundle\Controller;

use App\Entity\User;
use App\Repository\CongeRepository;
use App\Repository\AbsenceRepository;
use App\Repository\UserRepository;
use App\ReportBundle\Service\ReportGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/report')]
class ReportController extends AbstractController
{
    #[Route('/dashboard', name: 'report_dashboard')]
    public function dashboard(
        ReportGenerator $reportGenerator,
        UserRepository $userRepository
    ): Response {
        $user = $this->getUser();
        
        // Get stats based on user role
        if ($user->getRole() === 'MANAGER') {
            $stats = $reportGenerator->getManagerStats($user);
            $team = $userRepository->findBy(['user' => $user, 'role' => 'EMPLOYE']);
        } elseif ($user->getRole() === 'ADMIN_RH') {
            $stats = $reportGenerator->getGlobalStats();
            $team = [];
        } else {
            $stats = $reportGenerator->getEmployeeStats($user);
            $team = [];
        }
        
        return $this->render('@Report/report/dashboard.html.twig', [
            'stats' => $stats,
            'team' => $team,
            'user' => $user,
        ]);
    }
    
    #[Route('/team-absences', name: 'report_team_absences')]
    public function teamAbsences(
        ReportGenerator $reportGenerator,
        Request $request
    ): Response {
        $user = $this->getUser();
        
        if (!$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException('Accès réservé aux managers');
        }
        
        $year = $request->query->getInt('year', (int)date('Y'));
        $month = $request->query->getInt('month', (int)date('n'));
        
        $report = $reportGenerator->generateTeamAbsenceReport($user, $year, $month);
        
        return $this->render('@Report/report/team_absences.html.twig', [
            'report' => $report,
            'year' => $year,
            'month' => $month,
        ]);
    }
    
    #[Route('/leave-balance', name: 'report_leave_balance')]
    public function leaveBalance(
        ReportGenerator $reportGenerator,
        UserRepository $userRepository
    ): Response {
        $user = $this->getUser();
        
        if ($user->getRole() === 'MANAGER') {
            $balances = $reportGenerator->getTeamLeaveBalance($user);
        } elseif ($user->getRole() === 'ADMIN_RH') {
            $balances = $reportGenerator->getAllLeaveBalances();
        } else {
            $balances = [$reportGenerator->getEmployeeLeaveBalance($user)];
        }
        
        return $this->render('@Report/report/leave_balance.html.twig', [
            'balances' => $balances,
        ]);
    }
    
    #[Route('/monthly-summary', name: 'report_monthly_summary')]
    public function monthlySummary(
        ReportGenerator $reportGenerator,
        Request $request
    ): Response {
        $year = $request->query->getInt('year', (int)date('Y'));
        $month = $request->query->getInt('month', (int)date('n'));
        
        $summary = $reportGenerator->getMonthlySummary($year, $month);
        
        return $this->render('@Report/report/monthly_summary.html.twig', [
            'summary' => $summary,
            'year' => $year,
            'month' => $month,
        ]);
    }
    
    #[Route('/export/csv', name: 'report_export_csv')]
    public function exportCsv(
        ReportGenerator $reportGenerator,
        Request $request
    ): Response {
        $user = $this->getUser();
        $type = $request->query->get('type', 'absences');
        
        $csvContent = $reportGenerator->generateCsvExport($type, $user);
        
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="report_' . $type . '_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }
}
