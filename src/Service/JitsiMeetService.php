<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Entretien;

class JitsiMeetService
{
    public function __construct(
        private readonly string $baseUrl = 'https://meet.jit.si',
    ) {
    }

    /**
     * @return array{room:string,link:string}
     */
    public function createMeetingForInterview(Candidature $candidature, ?Entretien $entretien = null): array
    {
        $candidate = $candidature->getUser();
        $offer = $candidature->getOffreEmploi();
        $scheduledAt = $entretien?->getDateEntretien()?->format('YmdHi') ?? 'non-planifie';

        $parts = [
            'humadb',
            'entretien',
            'candidature',
            (string) $candidature->getId(),
            $scheduledAt,
            $offer?->getTitre() ?? 'offre',
            trim(($candidate?->getPrenom() ?? '').'-'.($candidate?->getNom() ?? '')),
        ];

        $room = preg_replace('/[^A-Za-z0-9]+/', '-', implode('-', $parts));
        $room = trim((string) $room, '-');
        $room = preg_replace('/-+/', '-', $room) ?: 'humadb-entretien-'.$candidature->getId();

        return [
            'room' => $room,
            'link' => rtrim($this->baseUrl, '/').'/'.$room,
        ];
    }

    /**
     * @return array{room:string,link:string}
     */
    public function createMeetingForCandidature(Candidature $candidature): array
    {
        return $this->createMeetingForInterview($candidature);
    }
}
