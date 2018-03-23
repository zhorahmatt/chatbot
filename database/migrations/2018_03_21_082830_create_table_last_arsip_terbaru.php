<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableLastArsipTerbaru extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegram_latest_archieve', function (Blueprint $table) {
            $table->increments('id');
            $table->text('last_archieve');
            $table->string('user_telegram');
            $table->string('chat_id');
            $table->string('last_command');
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
        Schema::dropIfExists('telegram_latest_archieve');
    }
}
