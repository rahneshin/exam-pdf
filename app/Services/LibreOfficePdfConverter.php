<?php

namespace App\Services;

use App\Exceptions\DocumentRenderException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Converts a .docx file to .pdf using LibreOffice headless.
 *
 * LibreOffice is used instead of Browsershot/Chromium because it opens the
 * .docx file natively and understands OMML math objects, RTL paragraph
 * direction and embedded fonts directly -- there is no lossy HTML/MathML
 * conversion step where equations or Persian text could break.
 */
class LibreOfficePdfConverter
{
    public function __construct(
        private readonly string $sofficeBinary,
        private readonly int $timeoutSeconds,
    ) {
    }

    /**
     * @return string Absolute path to the generated PDF.
     */
    public function convert(string $docxAbsolutePath, string $outputDirAbsolutePath): string
    {
        if (! is_executable($this->sofficeBinary)) {
            throw DocumentRenderException::binaryNotFound($this->sofficeBinary);
        }

        // Each conversion gets its own LibreOffice user profile directory.
        // Without this, concurrent conversions on the same server corrupt
        // each other's lock files and randomly fail.
        $userProfileDir = $outputDirAbsolutePath . '/lo-profile';

        $process = new Process([
            $this->sofficeBinary,
            '--headless',
            '--norestore',
            '--convert-to', 'pdf',
            '--outdir', $outputDirAbsolutePath,
            "-env:UserInstallation=file://{$userProfileDir}",
            $docxAbsolutePath,
        ]);

        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('LibreOffice conversion failed', [
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
            ]);

            throw DocumentRenderException::conversionFailed(
                'docx-to-pdf',
                $process->getErrorOutput() ?: 'Unknown LibreOffice error.'
            );
        }

        $expectedPdf = $outputDirAbsolutePath . '/' . pathinfo($docxAbsolutePath, PATHINFO_FILENAME) . '.pdf';

        if (! file_exists($expectedPdf)) {
            throw DocumentRenderException::conversionFailed(
                'docx-to-pdf',
                'LibreOffice reported success but no PDF was produced.'
            );
        }

        return $expectedPdf;
    }
}
