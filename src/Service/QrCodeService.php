<?php

namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeService
{
    public function generateQrCodeBase64(string $data, ?string $label = null): string
    {
        $builder = Builder::create()
            ->writer(new SvgWriter())
            ->writerOptions([])
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Low)
            ->size(300)
            ->margin(10);

        
        $qrCode = $builder->build();
        
        // Convert to base64
        $qrCodeBase64 = base64_encode($qrCode->getString());
        
        return $qrCodeBase64;
    }

    public function generateParticipationQrCodeData($participation): string
    {
        // Generate iCalendar format for calendar integration (date only, no time)
        $startDate = $participation->getDateInscription() ? $participation->getDateInscription()->format('Ymd') : date('Ymd');
        $endDate = $participation->getDateInscription() ? $participation->getDateInscription()->format('Ymd') : date('Ymd');
        
        $userName = ($participation->getUser()->getNom() ?? 'N/A') . ' ' . ($participation->getUser()->getPrenom() ?? '');
        $formationSubject = $participation->getFormation()->getSujet() ?? 'Formation';
        
        // Create iCalendar event data (professional format)
        $calendarData = "BEGIN:VCALENDAR\r\n";
        $calendarData .= "VERSION:2.0\r\n";
        $calendarData .= "PRODID:-//HUMA Formation//Participation Event//EN\r\n";
        $calendarData .= "CALSCALE:GREGORIAN\r\n";
        $calendarData .= "METHOD:PUBLISH\r\n";
        $calendarData .= "BEGIN:VEVENT\r\n";
        $calendarData .= "UID:participation_" . $participation->getId() . "@huma.tn\r\n";
        $calendarData .= "DTSTART;VALUE=DATE:" . $startDate . "\r\n";
        $calendarData .= "DTEND;VALUE=DATE:" . $endDate . "\r\n";
        $calendarData .= "DTSTAMP:" . date('Ymd\THis') . "\r\n";
        $calendarData .= "SUMMARY:" . $formationSubject . " - " . $userName . "\r\n";
        $calendarData .= "DESCRIPTION:Participation à la formation: " . $formationSubject . "\\n";
        $calendarData .= "Participant: " . $userName . "\\n";
        $calendarData .= "Email: " . ($participation->getUser()->getEmail() ?? 'N/A') . "\\n";
        $calendarData .= "Statut: " . ($participation->getStatut() ?? 'N/A') . "\r\n";
        $calendarData .= "STATUS:CONFIRMED\r\n";
        $calendarData .= "SEQUENCE:0\r\n";
        $calendarData .= "END:VEVENT\r\n";
        $calendarData .= "END:VCALENDAR";
        
        return $calendarData;
    }
}
