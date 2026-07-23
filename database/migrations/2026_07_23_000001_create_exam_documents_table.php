<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('original_filename');
            $table->enum('source_type', ['docx', 'json']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending');

            // Relative paths inside the private "local" disk.
            $table->string('source_path')->nullable();
            $table->string('docx_path')->nullable();
            $table->string('pdf_path')->nullable();

            $table->unsignedInteger('pages_count')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_documents');
    }
};
