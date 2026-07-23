<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->string('service_type');
            $table->date('event_date')->nullable();
            $table->string('location')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'answered'])->default('pending');
            $table->text('provider_response')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
