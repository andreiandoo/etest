<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('website_url')->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('sponsor_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('headline');
            $table->text('body')->nullable();
            $table->string('cta_label')->default('Află mai multe');
            $table->text('cta_url');
            $table->string('disclosure_label')->default('Conținut sponsorizat');
            $table->boolean('is_active')->default(true)->index();
            $table->timestampTz('starts_at')->nullable()->index();
            $table->timestampTz('ends_at')->nullable()->index();
            $table->unsignedInteger('priority')->default(0);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('sponsor_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vertical_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('taxonomy_node_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->nullable()->constrained('tests')->cascadeOnDelete();
            $table->string('placement', 50)->default('content');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['placement', 'vertical_id']);
            $table->index(['placement', 'taxonomy_node_id']);
            $table->index(['placement', 'test_id']);
        });

        Schema::create('lead_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vertical_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('taxonomy_node_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->nullable()->constrained('tests')->cascadeOnDelete();
            $table->string('name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cta_label')->default('Solicită informații');
            $table->jsonb('requested_fields')->nullable();
            $table->text('consent_text');
            $table->boolean('is_active')->default(true)->index();
            $table->timestampTz('starts_at')->nullable()->index();
            $table->timestampTz('ends_at')->nullable()->index();
            $table->unsignedInteger('priority')->default(0);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['vertical_id', 'is_active']);
            $table->index(['taxonomy_node_id', 'is_active']);
            $table->index(['test_id', 'is_active']);
        });

        Schema::create('lead_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestampTz('consented_at');
            $table->text('source_url')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->timestamps();

            $table->index(['lead_campaign_id', 'created_at']);
            $table->index(['email', 'created_at']);
        });

        Schema::create('affiliate_merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('website_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vertical_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('taxonomy_node_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->nullable()->constrained('tests')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('resource_type', 40)->default('book');
            $table->text('affiliate_url');
            $table->string('image_url')->nullable();
            $table->string('price_label')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestampTz('starts_at')->nullable()->index();
            $table->timestampTz('ends_at')->nullable()->index();
            $table->unsignedInteger('priority')->default(0);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['vertical_id', 'is_active']);
            $table->index(['taxonomy_node_id', 'is_active']);
            $table->index(['test_id', 'is_active']);
        });

        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('interest_key', 120);
            $table->foreignId('vertical_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('taxonomy_node_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->timestampTz('consented_at');
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('unsubscribed_at')->nullable();
            $table->text('source_url')->nullable();
            $table->timestamps();

            $table->unique(['email', 'interest_key']);
            $table->index(['interest_key', 'status']);
        });

        Schema::create('monetization_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 30);
            $table->foreignId('sponsor_placement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('affiliate_resource_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('source_url')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monetization_clicks');
        Schema::dropIfExists('newsletter_subscriptions');
        Schema::dropIfExists('affiliate_resources');
        Schema::dropIfExists('affiliate_merchants');
        Schema::dropIfExists('lead_submissions');
        Schema::dropIfExists('lead_campaigns');
        Schema::dropIfExists('sponsor_placements');
        Schema::dropIfExists('sponsor_campaigns');
        Schema::dropIfExists('sponsors');
    }
};
