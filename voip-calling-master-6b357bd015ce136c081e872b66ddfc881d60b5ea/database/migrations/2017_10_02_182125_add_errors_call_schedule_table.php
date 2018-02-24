<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddErrorsCallScheduleTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('call_schedules', function (Blueprint $table) {

			$table->unsignedInteger('call_errors')->default(0)->after('call_reconnects');

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

			$table->dropColumn(['call_errors']);

		});
	}
}
