<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('branding')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('host')->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['tenant_id', 'is_primary']);
        });

        Schema::create('tenant_vertical', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vertical_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['tenant_id', 'vertical_id']);
        });

        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_email')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key_prefix', 16)->index();
            $table->string('key_hash', 64)->unique();
            $table->jsonb('scopes');
            $table->unsignedInteger('daily_quota')->nullable();
            $table->unsignedInteger('monthly_quota')->nullable();
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampTz('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('api_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained()->cascadeOnDelete();
            $table->date('usage_date');
            $table->unsignedBigInteger('request_count')->default(0);
            $table->unsignedBigInteger('response_bytes')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->timestamps();

            $table->unique(['api_key_id', 'usage_date']);
            $table->index(['usage_date', 'request_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_daily');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('api_clients');
        Schema::dropIfExists('tenant_vertical');
        Schema::dropIfExists('tenant_domains');
        Schema::dropIfExists('tenants');
    }
};
