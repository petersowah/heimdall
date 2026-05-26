<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heimdall_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('heimdall_domains')->cascadeOnDelete();
            $table->enum('type', ['ssl', 'whois', 'uptime', 'dns']);
            $table->enum('status', ['ok', 'warning', 'critical', 'error']);
            $table->timestamp('checked_at');
            $table->integer('value')->nullable();
            $table->string('message')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'type', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heimdall_checks');
    }
};
