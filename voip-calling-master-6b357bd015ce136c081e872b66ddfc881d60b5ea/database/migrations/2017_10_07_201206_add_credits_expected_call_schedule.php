<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCreditsExpectedCallSchedule extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('call_schedules', function (Blueprint $table) {

			$table->unsignedInteger('credits_expected')->default(0)->after('call_errors');
			$table->unsignedInteger('credits_real')->nullable()->after('credits_expected');

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

			$table->dropColumn(['credits_expected']);
			$table->dropColumn(['credits_real']);

		});
	}
}
