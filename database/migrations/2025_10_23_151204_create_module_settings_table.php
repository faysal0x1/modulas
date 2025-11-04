<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('module_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module_key')->unique()->index(); // e.g., 'supercache', 'payment_gateway'
            $table->string('module_name'); // Human readable name
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('auto_register')->default(true);
            $table->string('provider_class')->nullable(); // Service provider class
            $table->json('settings')->nullable(); // Module-specific settings
            $table->json('dependencies')->nullable(); // Module dependencies
            $table->string('version')->nullable();
            $table->string('author')->nullable();
            $table->text('changelog')->nullable();
            $table->boolean('is_core')->default(false); // Core modules cannot be disabled
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_enabled', 'auto_register']);
            $table->index('is_core');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_settings');
    }
};
