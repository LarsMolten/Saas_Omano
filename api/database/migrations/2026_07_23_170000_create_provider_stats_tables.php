<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_type'); // visit, click_contact, click_quote, favorite
            $table->timestamp('created_at');

            $table->index(['provider_id', 'event_type', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('provider_stats_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('visits')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('contacts')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);
            $table->unsignedInteger('quote_requests_count')->default(0);
            $table->timestamps();

            $table->unique(['provider_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_stats_daily');
        Schema::dropIfExists('provider_events');
    }
};
