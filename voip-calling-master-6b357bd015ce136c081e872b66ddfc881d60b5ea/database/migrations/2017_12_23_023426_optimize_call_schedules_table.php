<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OptimizeCallSchedulesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasColumn('call_schedules', 'credits_changed')) {
			Schema::table('call_schedules', function (Blueprint $table) {
				$table->boolean('credits_changed')->default(false)->after('credits_real');
			});
		}

		if (Schema::hasColumn('call_schedules', 'port')) {
			Schema::table('call_schedules', function (Blueprint $table) {
				$table->dropColumn('port');
			});
		}

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		if (Schema::hasColumn('call_schedules', 'credits_changed')) {
			Schema::table('call_schedules', function (Blueprint $table) {
				$table->dropColumn('credits_changed');
			});
		}

		if (Schema::hasColumn('call_schedules', 'port')) {
			Schema::table('call_schedules', function (Blueprint $table) {
				$table->dropColumn('port');
			});
		}
	}
}
