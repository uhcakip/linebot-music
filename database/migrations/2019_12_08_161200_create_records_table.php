<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('records', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user', 33)->comment('Line 回傳的 userId');
            $table->string('keyword', 100)->nullable()->comment('使用者輸入的關鍵字');
            $table->enum('type', ['track', 'artist', 'album'])->default('track')->comment('搜尋範圍，track -> 歌名；artist -> 歌手名；album -> 專輯名');
            $table->enum('status', ['pending', 'completed'])->default('pending')->comment('狀態，pending -> 等待使用者輸入關鍵字；completed -> 查詢完畢');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('records');
    }
}
