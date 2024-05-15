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
        Schema::table('distress_score_ranges', function (Blueprint $table) {
            $table->longText('welcome_message')->nullable()->after('verdict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distress_score_ranges', function (Blueprint $table) {
            $table->dropColumn('welcome_message');
        });
    }
};
