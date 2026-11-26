<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'title']);
        });

        Schema::create('material_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            // Denormalized so duplicate uploads can be found per course
            // with one indexed lookup.
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->char('sha256', 64);
            $table->string('format', 16);
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('byte_size');
            $table->string('storage_path');
            $table->string('status', 16);
            $table->string('failure_reason', 64)->nullable();
            $table->longText('normalized_text')->nullable();
            $table->char('text_sha256', 64)->nullable();
            $table->unsignedInteger('character_count')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();

            $table->unique(['material_id', 'version']);
            $table->unique(['course_id', 'sha256']);
            $table->index(['material_id', 'status']);
        });

        Schema::create('source_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_version_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('heading')->nullable();
            $table->unsignedTinyInteger('level');
            // Offsets are Unicode code points into the version's normalized
            // text: start inclusive, end exclusive.
            $table->unsignedInteger('start');
            $table->unsignedInteger('end');
            $table->char('text_hash', 64);
            $table->timestamps();

            $table->unique(['material_version_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_sections');
        Schema::dropIfExists('material_versions');
        Schema::dropIfExists('materials');
    }
};
