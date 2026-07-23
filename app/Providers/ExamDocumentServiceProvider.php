<?php

namespace App\Providers;

use App\Services\JsonExamToDocxConverter;
use App\Services\LibreOfficePdfConverter;
use App\Services\PdfToImagesConverter;
use Illuminate\Support\ServiceProvider;

/**
 * The pipeline services take primitive config values (binary paths, DPI,
 * timeouts) in their constructors rather than reading config() internally,
 * which keeps them easy to unit test. This provider is where those
 * primitives are wired up from config/examdocs.php.
 */
class ExamDocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JsonExamToDocxConverter::class, function () {
            return new JsonExamToDocxConverter(
                defaultFont: config('examdocs.default_font'),
            );
        });

        $this->app->singleton(LibreOfficePdfConverter::class, function () {
            return new LibreOfficePdfConverter(
                sofficeBinary: config('examdocs.soffice_binary'),
                timeoutSeconds: config('examdocs.soffice_timeout'),
            );
        });

        $this->app->singleton(PdfToImagesConverter::class, function () {
            return new PdfToImagesConverter(
                pdftoppmBinary: config('examdocs.pdftoppm_binary'),
                dpi: config('examdocs.raster_dpi'),
                timeoutSeconds: config('examdocs.pdftoppm_timeout'),
            );
        });
    }
}
