<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReconnectsCallScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('call_schedules', function (Blueprint $table) {

		    $table->unsignedInteger('call_reconnects')->default(0)->after('call_status');

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

		    $table->dropColumn(['call_reconnects']);

	    });
    }
}
