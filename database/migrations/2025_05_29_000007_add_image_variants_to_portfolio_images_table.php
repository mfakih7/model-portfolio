<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_images', function (Blueprint $table) {
            $table->string('image_original')->nullable()->after('image_path');
            $table->string('image_large')->nullable()->after('image_original');
            $table->string('image_medium')->nullable()->after('image_large');
            $table->string('image_thumb')->nullable()->after('image_medium');
        });

        // image_path becomes a legacy/fallback column; new uploads populate the
        // dedicated variant columns instead, so it no longer needs to be required.
        Schema::table('portfolio_images', function (Blueprint $table) {
            $table->string('image_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_images', function (Blueprint $table) {
            $table->dropColumn(['image_original', 'image_large', 'image_medium', 'image_thumb']);
        });
    }
};
