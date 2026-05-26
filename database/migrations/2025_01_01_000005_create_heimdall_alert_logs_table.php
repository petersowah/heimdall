<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heimdall_alert_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('heimdall_domains')->cascadeOnDelete();
            $table->enum('channel', ['slack', 'telegram', 'email']);
            $table->string('alert_type');
            $table->timestamp('sent_at');
            $table->json('payload');
            $table->timestamps();

            $table->index(['domain_id', 'alert_type', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heimdall_alert_logs');
    }
};
