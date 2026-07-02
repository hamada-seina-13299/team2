<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('name');
            $table->time('attendance');
            $table->time('leaving');
            $table->time('break_start_time');
            $table->time('break_time');
            $table->string('working_place');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_masters');
    }
};