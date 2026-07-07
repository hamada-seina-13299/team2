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
        Schema::create('working_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // 申請者ID
            $table->date('target_date');                                      // 修正対象日
            
            // ステータス：デフォルト「申請中」、他は「承認」「却下」などの日本語文字列
            $table->string('status')->default('申請中');                     
            
            // 修正「前」のデータ（既存の打刻データ、または新規追加ならNull）
            $table->time('before_attendance')->nullable();
            $table->time('before_leaving')->nullable();
            $table->time('before_break_time')->nullable();
            $table->time('before_break_end_time')->nullable();
            $table->string('before_working_place')->nullable();
            
            // 修正「後」（ユーザーが申請した新しい時間・場所）
            $table->time('after_attendance')->nullable();
            $table->time('after_leaving')->nullable();
            $table->time('after_break_time')->nullable();
            $table->time('after_break_end_time')->nullable();
            $table->string('after_working_place')->nullable();
            
            //申請理由及び補足情報をまとめたカラム
            $table->text('memo')->nullable();
            //承認/却下したユーザーの名前を保存するカラムを追加
            $table->string('updater_name')->nullable();                                 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('working_corrections');
    }
};