<?php

return [

    // Absolute paths to system binaries. On most Debian/Ubuntu servers these
    // are correct after `apt install libreoffice poppler-utils`.
    'soffice_binary' => env('SOFFICE_BINARY', '/usr/bin/soffice'),
    'pdftoppm_binary' => env('PDFTOPPM_BINARY', '/usr/bin/pdftoppm'),

    // DPI used when rasterising each PDF page to PNG. 200-300 gives sharp
    // output for exam text and equations without producing huge files.
    'raster_dpi' => env('EXAMDOCS_RASTER_DPI', 220),

    // Seconds to allow each external process before it is killed.
    'soffice_timeout' => env('EXAMDOCS_SOFFICE_TIMEOUT', 120),
    'pdftoppm_timeout' => env('EXAMDOCS_PDFTOPPM_TIMEOUT', 60),

    // Private disk (see config/filesystems.php) where uploads and
    // intermediate/final artifacts are stored. Must NOT be publicly served.
    'disk' => env('EXAMDOCS_DISK', 'local'),

    // Storage sub-directories (relative to the disk root).
    'paths' => [
        'uploads' => 'exam-documents/uploads',
        'docx' => 'exam-documents/docx',
        'workdir' => 'exam-documents/work',
        'output' => 'exam-documents/output',
    ],

    // Font family to force on generated DOCX files (JSON source) so Persian
    // text renders correctly. The font must be installed on the server.
    'default_font' => env('EXAMDOCS_DEFAULT_FONT', 'Vazirmatn'),

];
