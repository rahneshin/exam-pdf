<?php

namespace App\Services;

use App\Exceptions\DocumentRenderException;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;

/**
 * Builds a .docx file from the exam JSON schema so it can enter the same
 * LibreOffice -> PDF -> PNG pipeline used for uploaded Word files. Keeping a
 * single rendering path (instead of a separate HTML/JSON renderer) is what
 * keeps the two source types visually consistent and easy to maintain.
 *
 * Expected JSON shape:
 * {
 *   "title": "آزمون ریاضی پایه دهم",
 *   "questions": [
 *     { "number": 1, "text": "...", "image_base64": null, "options": ["الف", "ب", "ج", "د"] }
 *   ]
 * }
 */
class JsonExamToDocxConverter
{
    public function __construct(private readonly string $defaultFont)
    {
    }

    public function convert(array $examData, string $destinationAbsolutePath): void
    {
        $phpWord = new PhpWord();
        $phpWord->getSettings()->setThemeFontLang(new Language(Language::FA_IR));
        $phpWord->setDefaultFontName($this->defaultFont);
        $phpWord->setDefaultFontSize(13);

        $section = $phpWord->addSection([
            'rtl' => true,
        ]);

        $titleStyle = ['bold' => true, 'size' => 18, 'name' => $this->defaultFont];
        $section->addText(
            $examData['title'] ?? 'برگه آزمون',
            $titleStyle,
            ['alignment' => Jc::CENTER, 'bidi' => true]
        );
        $section->addTextBreak(1);

        $questions = $examData['questions'] ?? [];

        if (empty($questions)) {
            throw DocumentRenderException::conversionFailed(
                'json-to-docx',
                'The JSON file does not contain a "questions" array.'
            );
        }

        foreach ($questions as $question) {
            $this->addQuestion($section, $question);
        }

        $phpWord->save($destinationAbsolutePath, 'Word2007');
    }

    private function addQuestion($section, array $question): void
    {
        $number = $question['number'] ?? '';
        $text = $question['text'] ?? '';

        $section->addText(
            "{$number}. {$text}",
            ['name' => $this->defaultFont, 'size' => 13],
            ['alignment' => Jc::START, 'bidi' => true, 'spaceAfter' => 120]
        );

        if (! empty($question['image_base64'])) {
            $this->addQuestionImage($section, $question['image_base64']);
        }

        foreach ($question['options'] ?? [] as $index => $option) {
            $letter = ['الف', 'ب', 'ج', 'د', 'ه'][$index] ?? ($index + 1);
            $section->addText(
                "{$letter}) {$option}",
                ['name' => $this->defaultFont, 'size' => 12],
                ['alignment' => Jc::START, 'bidi' => true, 'indentation' => ['start' => 400]]
            );
        }

        $section->addTextBreak(1);
    }

    private function addQuestionImage($section, string $base64): void
    {
        // Support both raw base64 and data-URI style strings.
        if (str_contains($base64, ',')) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }

        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            return;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'examimg_') . '.png';
        file_put_contents($tmpPath, $decoded);

        try {
            $section->addImage($tmpPath, ['width' => 350, 'height' => 220, 'alignment' => Jc::CENTER]);
        } finally {
            @unlink($tmpPath);
        }
    }
}
