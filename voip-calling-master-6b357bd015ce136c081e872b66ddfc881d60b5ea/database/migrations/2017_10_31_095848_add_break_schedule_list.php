<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBreakScheduleList extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('call_schedules', function (Blueprint $table) {
			$table->boolean('break')->default(false)->after('extended');
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
			$table->dropColumn(['break']);
		});
	}
}
