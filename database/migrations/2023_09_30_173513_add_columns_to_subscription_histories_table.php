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
        Schema::table('subscription_histories', function (Blueprint $table) {
            $table->date('grace_end')->nullable()->after('end_date');
            $table->boolean('auto_renew')->default(0)->after('grace_end');
            $table->integer('status')->default(1)->after('auto_renew');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_histories', function (Blueprint $table) {
            $table->dropColumn('grace_end');
            $table->dropColumn('auto_renew');
            $table->dropColumn('status');
        });
    }
};
