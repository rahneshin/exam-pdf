<?php

namespace App\Services;

use App\Exceptions\DocumentRenderException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Rasterises every page of a PDF into a high-resolution PNG using
 * pdftoppm (part of poppler-utils). poppler renders directly from the
 * PDF's vector content, so text, equations and embedded images all come
 * out sharp at the configured DPI -- there's no intermediate screenshot
 * step the way a browser-based renderer would need.
 */
class PdfToImagesConverter
{
    public function __construct(
        private readonly string $pdftoppmBinary,
        private readonly int $dpi,
        private readonly int $timeoutSeconds,
    ) {
    }

    /**
     * @return string[] Absolute paths to the generated PNGs, in page order.
     */
    public function convert(string $pdfAbsolutePath, string $outputDirAbsolutePath): array
    {
        if (! is_executable($this->pdftoppmBinary)) {
            throw DocumentRenderException::binaryNotFound($this->pdftoppmBinary);
        }

        $prefix = $outputDirAbsolutePath . '/page';

        $process = new Process([
            $this->pdftoppmBinary,
            '-png',
            '-r', (string) $this->dpi,
            $pdfAbsolutePath,
            $prefix,
        ]);

        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('pdftoppm conversion failed', [
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
            ]);

            throw DocumentRenderException::conversionFailed(
                'pdf-to-png',
                $process->getErrorOutput() ?: 'Unknown pdftoppm error.'
            );
        }

        $pages = glob($outputDirAbsolutePath . '/page-*.png') ?: [];

        // pdftoppm's default page suffix is zero-padded but sorts correctly
        // lexicographically only if we sort explicitly (glob order is not
        // guaranteed on every filesystem).
        natsort($pages);
        $pages = array_values($pages);

        if (empty($pages)) {
            throw DocumentRenderException::noPagesGenerated();
        }

        return $pages;
    }
}
