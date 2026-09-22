<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->index()->after('last_login_at');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('source_key')->nullable()->after('taxonomy_node_id');
            $table->foreignId('reviewed_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('published_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->unique(['vertical_id', 'source_key']);
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('published_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('content_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vertical_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32)->default('questions')->index();
            $table->string('format', 16);
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('created_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->jsonb('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_imports');

        Schema::table('tests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn('reviewed_at');
            $table->dropConstrainedForeignId('reviewed_by');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['vertical_id', 'source_key']);
            $table->dropColumn('source_key');
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn('reviewed_at');
            $table->dropConstrainedForeignId('reviewed_by');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
