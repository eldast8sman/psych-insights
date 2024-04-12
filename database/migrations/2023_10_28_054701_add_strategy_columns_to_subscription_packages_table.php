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
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->integer('listen_and_learn_limit')->default(1)->after('book_limit');
            $table->integer('read_and_reflect_limit')->default(1)->after('listen_and_learn_limit');
            $table->integer('learn_and_do_limit')->default(1)->after('read_and_reflect_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn('listen_and_learn_limit');
            $table->dropColumn('read_and_reflect_limit');
            $table->dropColumn('learn_and_do_limit');
        });
    }
};
