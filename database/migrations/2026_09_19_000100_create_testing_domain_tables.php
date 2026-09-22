<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verticals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->smallInteger('sort_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('taxonomy_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vertical_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('taxonomy_nodes')->cascadeOnDelete();
            $table->string('type', 32)->index();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['vertical_id', 'slug']);
            $table->index(['vertical_id', 'type']);
        });

        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vertical_id')->constrained()->cascadeOnDelete();
            $table->foreignId('taxonomy_node_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('mode', 32)->default('practice')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedSmallInteger('question_limit')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->decimal('passing_percentage', 5, 2)->nullable();
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_options')->default(false);
            $table->boolean('allow_review')->default(true);
            $table->boolean('show_explanations')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['vertical_id', 'slug']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vertical_id')->constrained()->cascadeOnDelete();
            $table->foreignId('taxonomy_node_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->text('prompt');
            $table->text('explanation')->nullable();
            $table->unsignedSmallInteger('difficulty')->default(3)->index();
            $table->string('source_label')->nullable();
            $table->text('source_url')->nullable();
            $table->date('source_checked_at')->nullable();
            $table->jsonb('answer_config')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vertical_id', 'status', 'type']);
        });

        Schema::create('answer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_correct')->default(false)->index();
            $table->unsignedSmallInteger('position')->default(0);
            $table->text('feedback')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['question_id', 'position']);
        });

        Schema::create('test_question', function (Blueprint $table) {
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->decimal('points', 8, 2)->default(1);
            $table->boolean('required')->default(true);
            $table->jsonb('settings')->nullable();

            $table->primary(['test_id', 'question_id']);
            $table->unique(['test_id', 'position']);
        });

        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->string('status', 32)->default('in_progress')->index();
            $table->string('mode', 32);
            $table->bigInteger('seed');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->decimal('score', 10, 2)->default(0);
            $table->decimal('max_score', 10, 2)->default(0);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('current_position')->default(0);
            $table->jsonb('configuration')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['test_id', 'status']);
        });

        Schema::create('attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('position');
            $table->decimal('points', 8, 2)->default(1);
            $table->jsonb('option_order')->nullable();
            $table->jsonb('question_snapshot');
            $table->timestamps();

            $table->unique(['test_attempt_id', 'position']);
        });

        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_question_id')->unique()->constrained()->cascadeOnDelete();
            $table->jsonb('answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('awarded_points', 8, 2)->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('attempt_questions');
        Schema::dropIfExists('test_attempts');
        Schema::dropIfExists('test_question');
        Schema::dropIfExists('answer_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('tests');
        Schema::dropIfExists('taxonomy_nodes');
        Schema::dropIfExists('verticals');
    }
};
