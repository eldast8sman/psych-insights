<?php

use App\Models\ReadAndReflect;
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
        Schema::create('read_and_reflect_reflections', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ReadAndReflect::class, 'read_and_reflect_id');
            $table->text('reflection');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('read_and_reflect_reflections');
    }
};
