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
        Schema::create('shifts', function (Blueprint $table) {
            // シフトID（主キー、自動採番）
            $table->id();
 
            // ユーザーID
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
 
            // シフトマスタID
            $table->foreignId('master_id')->constrained('shift_masters')->onDelete('cascade');
 
            // メモ
            $table->string('memo');
 
            // 修正後出勤時刻
            $table->time('attendance_edit');
 
            // 修正後退勤時刻
            $table->time('leaving_edit');
 
            // 対象日
            $table->date('target_date');

            // ステータス
            $table->string('status', 10);
 
            // 作成日時、更新日時
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
