<?php

namespace App\Services;

use App\Exceptions\DocumentRenderException;
use FPDF;

/**
 * Assembles a set of page PNGs into a single, image-only PDF -- one full
 * page per image, no text layer, no OCR. Each page is sized to match its
 * source image so the exam's original layout/orientation is preserved
 * exactly rather than being forced into a fixed A4 frame.
 *
 * Uses FPDF rather than TCPDF: this step never renders text (only places
 * pre-rasterised page images), and FPDF's built-in core fonts ship as
 * plain PHP files with the package -- no separate font-asset build step
 * is required, unlike tecnickcom/tcpdf >=7, which needs its fonts
 * generated via a FontForge-based build pipeline that isn't run by a
 * normal `composer install`.
 */
class ImagesToPdfMerger
{
    /**
     * @param string[] $pngAbsolutePaths
     */
    public function merge(array $pngAbsolutePaths, string $destinationAbsolutePath): void
    {
        $pdf = new FPDF('P', 'pt', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

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
            $pdf->Image($imagePath, 0, 0, $widthPt, $heightPt);
        }

        if ($pdf->PageNo() === 0) {
            throw DocumentRenderException::noPagesGenerated();
        }

        $pdf->Output('F', $destinationAbsolutePath);
    }

    private function guessDpi(string $imagePath): int
    {
        // getimagesize doesn't expose DPI reliably for PNG; we fall back to
        // the DPI the pipeline requested pdftoppm to render at, injected via
        // config so page size stays proportionate to the on-screen render.
        return config('examdocs.raster_dpi', 220);
    }
}