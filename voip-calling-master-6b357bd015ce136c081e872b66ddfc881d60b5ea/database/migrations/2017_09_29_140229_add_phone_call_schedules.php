<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPhoneCallSchedules extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

		Schema::table('call_schedules', function (Blueprint $table) {
			$table->dropColumn(['call_phone', 'status', 'done']);
		});

		Schema::table('call_schedules', function (Blueprint $table) {

			$table->string('call_phone')->nullable()->after('schedule_date');
			$table->boolean('call_finished')->default(false)->after('call_phone');
			$table->unsignedSmallInteger('call_status')->default(0)->after('call_finished');
			$table->string('phone')->nullable()->after('port');

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
			$table->dropColumn(['phone', 'call_phone', 'call_finished']);
		});
	}
}
