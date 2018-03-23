<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddArrayValueOnLastFindArchieve extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('telegram_find_archieve', function (Blueprint $table) {
            $table->string('array_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('telegram_find_archieve', function (Blueprint $table) {
            $table->dropColumn('array_value');
        });
    }
}
