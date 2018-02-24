<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddScheduleIdScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('call_schedules', function (Blueprint $table) {

		    $table->boolean('extended')->default(false)->after('credits_real');

	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('call_schedules', function (Blueprint $table) {

		    $table->dropColumn(['extended']);

	    });
    }
}
