<?php

namespace App\WorkflowBundle\Service;

use App\Entity\Conge;
use App\Entity\Absence;
use App\WorkflowBundle\Entity\ApprovalHistory;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;

class ApprovalWorkflow
{
    private EntityManagerInterface $em;
    private NotificationService $notificationService;
    
    public function __construct(EntityManagerInterface $em, NotificationService $notificationService)
    {
        $this->em = $em;
        $this->notificationService = $notificationService;
    }
    
    /**
     * Normalize status value
     */
    private function normalizeStatus(string $status): string
    {
        return strtolower(str_replace('_', ' ', trim($status)));
    }
    
    private function isPending(string $status): bool
    {
        $normalized = $this->normalizeStatus($status);
        return in_array($normalized, ['en_attente', 'en attente']);
    }
    
    /**
     * Approve a conge request
     */
    public function approveConge(Conge $conge, $approver, string $comment = ''): array
    {
        $absence = $conge->getAbsence();
        
        if (!$this->isPending($absence->getStatut())) {
            return [
                'success' => false,
                'message' => 'Cette demande a déjà été traitée'
            ];
        }
        
        // Update status
        $absence->setStatut('approuve');
        
        // Create history entry
        $history = new ApprovalHistory();
        $history->setConge($conge);
        $history->setAbsence($absence);
        $history->setApprover($approver);
        $history->setAction('approve');
        $history->setComment($comment);
        $history->setCreatedAt(new \DateTime());
        
        $this->em->persist($absence);
        $this->em->persist($history);
        $this->em->flush();
        
        // Send notification to employee
        $this->notificationService->notifyEmployeeRequestApproved($conge, $comment);
        
        return [
            'success' => true,
            'message' => 'Le congé a été approuvé avec succès'
        ];
    }
    
    /**
     * Reject a conge request
     */
    public function rejectConge(Conge $conge, $approver, string $comment): array
    {
        $absence = $conge->getAbsence();
        
        if (!$this->isPending($absence->getStatut())) {
            return [
                'success' => false,
                'message' => 'Cette demande a déjà été traitée'
            ];
        }
        
        // Update status
        $absence->setStatut('refuse');
        
        // Create history entry
        $history = new ApprovalHistory();
        $history->setConge($conge);
        $history->setAbsence($absence);
        $history->setApprover($approver);
        $history->setAction('reject');
        $history->setComment($comment);
        $history->setCreatedAt(new \DateTime());
        
        $this->em->persist($absence);
        $this->em->persist($history);
        $this->em->flush();
        
        // TODO: Send notification to employee
        
        return [
            'success' => true,
            'message' => 'Le congé a été refusé'
        ];
    }
    
    /**
     * Approve an absence request
     */
    public function approveAbsence(Absence $absence, $approver, string $comment = ''): array
    {
        if (!$this->isPending($absence->getStatut())) {
            return [
                'success' => false,
                'message' => 'Cette demande a déjà été traitée'
            ];
        }
        
        // Update status
        $absence->setStatut('approuve');
        
        // Create history entry
        $history = new ApprovalHistory();
        $history->setAbsence($absence);
        $history->setApprover($approver);
        $history->setAction('approve');
        $history->setComment($comment);
        $history->setCreatedAt(new \DateTime());
        
        $this->em->persist($absence);
        $this->em->persist($history);
        $this->em->flush();
        
        return [
            'success' => true,
            'message' => 'L\'absence a été approuvée avec succès'
        ];
    }
    
    /**
     * Reject an absence request
     */
    public function rejectAbsence(Absence $absence, $approver, string $comment): array
    {
        if (!$this->isPending($absence->getStatut())) {
            return [
                'success' => false,
                'message' => 'Cette demande a déjà été traitée'
            ];
        }
        
        // Update status
        $absence->setStatut('refuse');
        
        // Create history entry
        $history = new ApprovalHistory();
        $history->setAbsence($absence);
        $history->setApprover($approver);
        $history->setAction('reject');
        $history->setComment($comment);
        $history->setCreatedAt(new \DateTime());
        
        $this->em->persist($absence);
        $this->em->persist($history);
        $this->em->flush();
        
        // Send notification to employee
        $this->notificationService->notifyEmployeeRequestRejected($absence, $comment);
        
        return [
            'success' => true,
            'message' => 'L\'absence a été refusée'
        ];
    }
    
    /**
     * Get approval statistics for a manager
     */
    public function getManagerStats($manager): array
    {
        // TODO: Implement statistics
        return [
            'pending_count' => 0,
            'approved_this_month' => 0,
            'rejected_this_month' => 0,
        ];
    }
}
