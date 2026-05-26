<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heimdall_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('check_interval_minutes')->default(5);
            $table->boolean('notify_ssl')->default(true);
            $table->boolean('notify_domain_expiry')->default(true);
            $table->boolean('notify_uptime')->default(true);
            $table->boolean('notify_dns')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heimdall_domains');
    }
};
