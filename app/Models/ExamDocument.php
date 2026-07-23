<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExamDocument extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid',
        'user_id',
        'original_filename',
        'source_type',
        'status',
        'source_path',
        'docx_path',
        'pdf_path',
        'pages_count',
        'error_message',
        'processing_started_at',
        'completed_at',
    ];

    protected $casts = [
        'processing_started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted(): void
    {
        static::creating(function (ExamDocument $document) {
            $document->uuid ??= (string) Str::uuid();
        });
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'processing_started_at' => now(),
        ]);
    }

    public function markCompleted(string $pdfPath, int $pagesCount): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'pdf_path' => $pdfPath,
            'pages_count' => $pagesCount,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $message,
        ]);
    }
}
