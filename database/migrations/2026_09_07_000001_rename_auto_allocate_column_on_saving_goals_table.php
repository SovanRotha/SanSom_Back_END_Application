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
        Schema::table('saving_goals', function (Blueprint $table) {
            $table->renameColumn('auto-allocate', 'auto_allocate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saving_goals', function (Blueprint $table) {
            $table->renameColumn('auto_allocate', 'auto-allocate');
        });
    }
};
