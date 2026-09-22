<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stats', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('xp')->default(0);
            $table->unsignedInteger('completed_attempts')->default(0);
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->boolean('leaderboard_opt_in')->default(false)->index();
            $table->string('leaderboard_display_name', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('favorite_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'test_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('achievement_key', 64);
            $table->timestamp('unlocked_at');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'achievement_key']);
            $table->index(['user_id', 'unlocked_at']);
        });

        Schema::create('user_xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_attempt_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('points');
            $table->string('reason', 64);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['test_attempt_id', 'reason']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_xp_events');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('favorite_tests');
        Schema::dropIfExists('user_stats');
    }
};
