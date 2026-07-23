<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown at any stage of the render pipeline (docx conversion, PDF
 * conversion, image rasterisation, or PDF assembly) so the job layer
 * has a single exception type to catch and persist on the model.
 */
class DocumentRenderException extends Exception
{
    public static function conversionFailed(string $step, string $details): self
    {
        return new self("Rendering step [{$step}] failed: {$details}");
    }

    public static function binaryNotFound(string $binary): self
    {
        return new self("Required binary [{$binary}] was not found or is not executable. Check config/examdocs.php.");
    }

    public static function noPagesGenerated(): self
    {
        return new self('The rendering pipeline produced zero pages.');
    }
}
