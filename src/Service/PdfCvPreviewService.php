<?php

namespace App\Service;

use Smalot\PdfParser\Parser;

class PdfCvPreviewService
{
    public function __construct(private readonly Parser $parser)
    {
    }

    public function extractPreview(?string $absolutePath, int $limit = 1800): ?string
    {
        if ($absolutePath === null || !is_file($absolutePath)) {
            return null;
        }

        try {
            $pdf = $this->parser->parseFile($absolutePath);
            $text = trim(preg_replace('/\s+/', ' ', $pdf->getText()) ?? '');

            if ($text === '') {
                return null;
            }

            if (mb_strlen($text) <= $limit) {
                return $text;
            }

            return mb_substr($text, 0, $limit).'...';
        } catch (\Throwable) {
            return null;
        }
    }
}
