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
        Schema::table('current_subscriptions', function (Blueprint $table) {
            $table->date('grace_end')->nullable();
            $table->boolean('auto_renew');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('current_subscriptions', function (Blueprint $table) {
            $table->dropColumn('grace_end');
            $table->dropColumn('auto_renew');
        });
    }
};
