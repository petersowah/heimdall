<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heimdall_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('heimdall_domains')->cascadeOnDelete();
            $table->enum('type', ['uptime', 'ssl', 'dns', 'whois']);
            $table->enum('status', ['open', 'resolved'])->default('open');
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->text('details');
            $table->timestamps();

            $table->index(['domain_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heimdall_incidents');
    }
};
