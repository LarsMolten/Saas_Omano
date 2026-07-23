<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->between(1, 5);
            $table->text('comment')->nullable();
            $table->string('status')->default('published')->index();
            $table->timestamps();

            $table->unique(['user_id', 'provider_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('rating_count')->default(0)->after('average_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rating_count');
        });
    }
};
