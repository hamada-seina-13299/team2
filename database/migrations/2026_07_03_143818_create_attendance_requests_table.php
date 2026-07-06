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
        Schema::create('attendance_requests', function (Blueprint $table) {
            // 勤怠申請ID（主キー、自動採番）
            $table->id();
 
            // ユーザーID
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // 対象日
            $table->date('target_date');

            // 申請種別
            $table->string('request_type');
 
            // メモ
            $table->string('memo');            
 
            // 申請時刻
            $table->time('request_time')->nullable();
 
            // 添付ファイル
            $table->string('attachment')->nullable();

            // 作成日時、更新日時
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_requests');
    }
};
