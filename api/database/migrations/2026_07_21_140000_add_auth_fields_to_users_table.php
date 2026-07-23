<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['client', 'prestataire', 'admin'])->default('client')->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->boolean('phone_verified')->default(false)->after('phone');
            $table->string('email_verification_token')->nullable()->after('email_verified_at');
            $table->string('phone_verification_code')->nullable()->after('phone_verified');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'phone',
                'phone_verified',
                'email_verification_token',
                'phone_verification_code',
            ]);
        });
    }
};
