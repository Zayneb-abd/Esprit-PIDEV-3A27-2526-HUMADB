<?php

namespace App\Service;

use App\Entity\Formation;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class QRCodeServiceFixed
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function generateFormationQRCode(Formation $formation): string
    {
        // Create calendar event data
        $calendarData = $this->generateCalendarData($formation);
        
        // Generate QR code with calendar data
        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($calendarData)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Low)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();

        // Save QR code to public directory
        $qrCodePath = 'qrcodes/formation_' . $formation->getId() . '.png';
        $fullPath = $this->projectDir . '/public/' . $qrCodePath;
        
        // Ensure directory exists
        $qrCodeDir = dirname($fullPath);
        if (!is_dir($qrCodeDir)) {
            mkdir($qrCodeDir, 0777, true);
        }
        
        $qrCode->saveToFile($fullPath);
        
        return $qrCodePath;
    }

    private function generateCalendarData(Formation $formation): string
    {
        // Extract location from localisation if it contains coordinates
        $location = $formation->getLocalisation() ?? 'Non spécifié';
        $description = $formation->getLocalisation() ?? '';
        
        // Check if localisation contains coordinates (format: lat,lng)
        if (preg_match('/(-?\d+\.\d+),\s*(-?\d+\.\d+)/', $description, $matches)) {
            $lat = $matches[1];
            $lng = $matches[2];
            $location = "Formation: {$formation->getSujet()} | Coordonnées: {$lat},{$lng}";
        }
        
        // Create calendar event data in iCalendar format optimized for mobile
        $startDate = $formation->getDateDebut() ? $formation->getDateDebut()->format('Ymd\THis') : date('Ymd\THis');
        $endDate = $formation->getDateFin() ? $formation->getDateFin()->format('Ymd\THis') : date('Ymd\THis', strtotime('+2 hours'));
        
        // Format for better mobile calendar compatibility
        $calendarData = "BEGIN:VCALENDAR\r\n";
        $calendarData .= "VERSION:2.0\r\n";
        $calendarData .= "PRODID:-//HUMA Formation//Formation Event//EN\r\n";
        $calendarData .= "CALSCALE:GREGORIAN\r\n";
        $calendarData .= "METHOD:PUBLISH\r\n";
        $calendarData .= "BEGIN:VEVENT\r\n";
        $calendarData .= "UID:formation_" . $formation->getId() . "@huma.tn\r\n";
        $calendarData .= "DTSTART:" . $startDate . "\r\n";
        $calendarData .= "DTEND:" . $endDate . "\r\n";
        $calendarData .= "DTSTAMP:" . $startDate . "\r\n";
        $calendarData .= "SUMMARY:" . $formation->getSujet() . "\r\n";
        $calendarData .= "DESCRIPTION:" . str_replace(["\r", "\n"], ["\\r", "\\n"], $description) . "\r\n";
        $calendarData .= "LOCATION:" . $location . "\r\n";
        $calendarData .= "STATUS:CONFIRMED\r\n";
        $calendarData .= "SEQUENCE:0\r\n";
        $calendarData .= "END:VEVENT\r\n";
        $calendarData .= "END:VCALENDAR";
        
        return $calendarData;
    }

    public function extractCoordinatesFromDescription(string $description): ?array
    {
        if (preg_match('/(-?\d+\.\d+),\s*(-?\d+\.\d+)/', $description, $matches)) {
            return [
                'latitude' => (float) $matches[1],
                'longitude' => (float) $matches[2]
            ];
        }
        return null;
    }
}
