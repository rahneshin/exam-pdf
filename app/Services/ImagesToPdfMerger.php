<?php

namespace App\Services;

use App\Exceptions\DocumentRenderException;
use TCPDF;

/**
 * Assembles a set of page PNGs into a single, image-only PDF -- one full
 * page per image, no text layer, no OCR. Each page is sized to match its
 * source image so the exam's original layout/orientation is preserved
 * exactly rather than being forced into a fixed A4 frame.
 */
class ImagesToPdfMerger
{
    /**
     * @param string[] $pngAbsolutePaths
     */
    public function merge(array $pngAbsolutePaths, string $destinationAbsolutePath): void
    {
        $pdf = new TCPDF(orientation: 'P', unit: 'pt', format: 'A4');

        // Strip metadata as much as TCPDF allows.
        $pdf->SetCreator('');
        $pdf->SetAuthor('');
        $pdf->SetTitle('');
        $pdf->SetSubject('');
        $pdf->SetKeywords('');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);

        foreach ($pngAbsolutePaths as $imagePath) {
            [$widthPx, $heightPx] = getimagesize($imagePath) ?: [null, null];

            if (! $widthPx || ! $heightPx) {
                throw DocumentRenderException::conversionFailed(
                    'merge-images',
                    "Could not read image dimensions for [{$imagePath}]."
                );
            }

            // Convert pixels (rendered at the configured DPI) to points (72/in).
            $dpi = $this->guessDpi($imagePath);
            $widthPt = $widthPx / $dpi * 72;
            $heightPt = $heightPx / $dpi * 72;

            $orientation = $widthPt > $heightPt ? 'L' : 'P';
            $pdf->AddPage($orientation, [$widthPt, $heightPt]);
            $pdf->Image($imagePath, 0, 0, $widthPt, $heightPt, '', '', '', false, 300);
        }

        if ($pdf->getNumPages() === 0) {
            throw DocumentRenderException::noPagesGenerated();
        }

        $pdf->Output($destinationAbsolutePath, 'F');
    }

    private function guessDpi(string $imagePath): int
    {
        $info = getimagesize($imagePath);
        $resolution = $info['channels'] ?? null; // not reliable via getimagesize alone

        // getimagesize doesn't expose DPI reliably for PNG; we fall back to
        // the DPI the pipeline requested pdftoppm to render at, injected via
        // config so page size stays proportionate to the on-screen render.
        return config('examdocs.raster_dpi', 220);
    }
}
