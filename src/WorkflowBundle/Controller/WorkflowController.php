<?php

namespace App\WorkflowBundle\Controller;

use App\Entity\Conge;
use App\Entity\Absence;
use App\Repository\CongeRepository;
use App\Repository\AbsenceRepository;
use App\WorkflowBundle\Service\ApprovalWorkflow;
use App\WorkflowBundle\Repository\ApprovalHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/workflow')]
class WorkflowController extends AbstractController
{
    #[Route('/conge/{id}/approve', name: 'workflow_conge_approve', methods: ['POST'])]
    public function approveConge(
        int $id,
        Request $request,
        CongeRepository $congeRepository,
        ApprovalWorkflow $workflow
    ): Response {
        $conge = $congeRepository->find($id);
        if (!$conge) {
            throw $this->createNotFoundException('Congé non trouvé');
        }
        
        $user = $this->getUser();
        $comment = $request->request->get('comment', '');
        
        // Check if user can approve (manager of employee or admin)
        if (!$this->canApprove($user, $conge)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour approuver ce congé');
            return $this->redirectToRoute('manager_conges');
        }
        
        $result = $workflow->approveConge($conge, $user, $comment);
        
        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }
        
        return $this->redirectToRoute('manager_conges');
    }
    
    #[Route('/conge/{id}/reject', name: 'workflow_conge_reject', methods: ['POST'])]
    public function rejectConge(
        int $id,
        Request $request,
        CongeRepository $congeRepository,
        ApprovalWorkflow $workflow
    ): Response {
        $conge = $congeRepository->find($id);
        if (!$conge) {
            throw $this->createNotFoundException('Congé non trouvé');
        }
        
        $user = $this->getUser();
        $comment = $request->request->get('comment', '');
        
        if (empty($comment)) {
            $this->addFlash('error', 'Un commentaire est obligatoire pour refuser une demande');
            return $this->redirectToRoute('manager_conges');
        }
        
        if (!$this->canApprove($user, $conge)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour refuser ce congé');
            return $this->redirectToRoute('manager_conges');
        }
        
        $result = $workflow->rejectConge($conge, $user, $comment);
        
        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }
        
        return $this->redirectToRoute('manager_conges');
    }
    
    #[Route('/absence/{id}/approve', name: 'workflow_absence_approve', methods: ['POST'])]
    public function approveAbsence(
        int $id,
        Request $request,
        AbsenceRepository $absenceRepository,
        ApprovalWorkflow $workflow
    ): Response {
        $absence = $absenceRepository->find($id);
        if (!$absence) {
            throw $this->createNotFoundException('Absence non trouvée');
        }
        
        $user = $this->getUser();
        $comment = $request->request->get('comment', '');
        
        if (!$this->canApprove($user, $absence->getConge())) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour approuver cette absence');
            return $this->redirectToRoute('manager_absences');
        }
        
        $result = $workflow->approveAbsence($absence, $user, $comment);
        
        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }
        
        return $this->redirectToRoute('manager_absences');
    }
    
    #[Route('/absence/{id}/reject', name: 'workflow_absence_reject', methods: ['POST'])]
    public function rejectAbsence(
        int $id,
        Request $request,
        AbsenceRepository $absenceRepository,
        ApprovalWorkflow $workflow
    ): Response {
        $absence = $absenceRepository->find($id);
        if (!$absence) {
            throw $this->createNotFoundException('Absence non trouvée');
        }
        
        $user = $this->getUser();
        $comment = $request->request->get('comment', '');
        
        if (empty($comment)) {
            $this->addFlash('error', 'Un commentaire est obligatoire pour refuser une demande');
            return $this->redirectToRoute('manager_absences');
        }
        
        if (!$this->canApprove($user, $absence->getConge())) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour refuser cette absence');
            return $this->redirectToRoute('manager_absences');
        }
        
        $result = $workflow->rejectAbsence($absence, $user, $comment);
        
        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }
        
        return $this->redirectToRoute('manager_absences');
    }
    
    #[Route('/history', name: 'workflow_history')]
    public function history(ApprovalHistoryRepository $historyRepo): Response
    {
        $user = $this->getUser();
        
        // Get approval history based on user role
        if ($user->getRole() === 'MANAGER') {
            // Get all team history where approver is this manager
            $history = $historyRepo->findByApprover($user);
        } elseif ($user->getRole() === 'ADMIN_RH') {
            // Get all history
            $history = $historyRepo->findAllOrdered();
        } else {
            // Employee: get history for their requests
            $history = $historyRepo->findByEmployee($user);
        }
        
        return $this->render('@Workflow/workflow/history.html.twig', [
            'history' => $history,
        ]);
    }
    
    private function canApprove($user, $conge): bool
    {
        if ($user->getRole() === 'ADMIN_RH') {
            return true;
        }
        
        if ($user->getRole() === 'MANAGER') {
            // Manager can approve if employee is in their team
            $employee = $conge->getUser();
            return $employee->getUser() === $user; // employee's manager is the current user
        }
        
        return false;
    }
}
