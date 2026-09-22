<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attempt_question_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 32)->index();
            $table->text('message')->nullable();
            $table->string('status', 32)->default('open')->index();
            $table->jsonb('context')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['question_id', 'status']);
        });

        Schema::create('question_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('attempts_count')->default(0);
            $table->unsignedBigInteger('answered_count')->default(0);
            $table->unsignedBigInteger('correct_count')->default(0);
            $table->decimal('total_awarded_points', 14, 2)->default(0);
            $table->decimal('total_possible_points', 14, 2)->default(0);
            $table->decimal('correct_rate', 7, 4)->default(0);
            $table->unsignedBigInteger('avg_duration_ms')->default(0);
            $table->timestamp('last_answered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_statistics');
        Schema::dropIfExists('question_reports');
    }
};
