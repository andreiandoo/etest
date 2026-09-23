<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registrul de surse și documentele lor.
 *
 * Câmpurile vin din §9 al inventarului de conținut și din §31 al dosarului de
 * cercetare. Rostul lor e să facă importul verificabil mai târziu: de unde a
 * venit întrebarea, din ce fișier, din ce versiune a lui și în ce condiții de
 * reutilizare.
 *
 * Documentele se păstrează cu amprentă SHA-256 și nu se suprascriu niciodată.
 * O sincronizare nouă adaugă un rând; dacă amprenta e aceeași, sursa nu s-a
 * schimbat și importul poate fi sărit. Așa se vede și când o autoritate
 * modifică tăcut un PDF sub aceeași adresă.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('authority');
            $table->string('title');
            $table->string('exam')->nullable();
            $table->string('specialty')->nullable();
            $table->foreignId('vertical_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('taxonomy_node_id')->nullable()->constrained()->nullOnDelete();
            $table->text('source_page_url')->nullable();
            $table->text('document_url');
            $table->text('license_url')->nullable();
            $table->string('version_label')->nullable();
            $table->string('file_format', 16)->default('pdf');
            $table->string('rights_status', 32)->index();
            $table->string('answer_key', 32)->default('unknown');
            $table->string('explanations', 32)->default('unknown');
            $table->string('import_difficulty', 32)->default('mixed');
            $table->string('review_status', 32)->default('discovered')->index();
            $table->string('count_status', 40)->default('count_pending');
            $table->unsignedInteger('item_count_raw')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('source_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_import_id')->nullable()->constrained()->nullOnDelete();
            $table->string('storage_path');
            $table->string('text_path')->nullable();
            $table->string('mime_type', 100)->default('application/pdf');
            $table->string('sha256', 64)->index();
            $table->unsignedBigInteger('byte_size');
            $table->string('version_label')->nullable();
            $table->unsignedInteger('item_count')->nullable();
            $table->unsignedInteger('imported_count')->nullable();
            $table->unsignedInteger('rejected_count')->nullable();
            $table->jsonb('rejected')->nullable();
            $table->timestamp('retrieved_at');
            $table->timestamps();

            $table->unique(['source_id', 'sha256']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_documents');
        Schema::dropIfExists('sources');
    }
};
