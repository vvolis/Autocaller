<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddScheduleIdPhoneCallFailLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('phone_call_fail_logs', function (Blueprint $table) {

		    $table->unsignedInteger('schedule_id')->nullable()->after('phone');

	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('phone_call_fail_logs', function (Blueprint $table) {

		    $table->dropColumn(['schedule_id']);

	    });
    }
}
