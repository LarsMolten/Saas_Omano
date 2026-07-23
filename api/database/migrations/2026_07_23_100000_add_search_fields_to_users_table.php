<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('role');
            $table->string('category')->nullable()->after('bio');
            $table->string('city')->nullable()->after('category');
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('average_rating', 3, 2)->default(0)->after('longitude');
        });

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        DB::statement("
            ALTER TABLE users ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                setweight(to_tsvector('simple', coalesce(name, '')), 'A') ||
                setweight(to_tsvector('simple', coalesce(bio, '')), 'B') ||
                setweight(to_tsvector('simple', coalesce(city, '')), 'B') ||
                setweight(to_tsvector('simple', coalesce(category, '')), 'C')
            ) STORED
        ");

        DB::statement('CREATE INDEX users_search_gin_idx ON users USING GIN (search_vector)');
        DB::statement('CREATE INDEX users_city_trgm_idx ON users USING GIN (city gin_trgm_ops)');
        DB::statement('CREATE INDEX users_name_trgm_idx ON users USING GIN (name gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bio', 'category', 'city', 'latitude', 'longitude',
                'average_rating', 'search_vector',
            ]);
        });
    }
};
