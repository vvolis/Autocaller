<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCallerNumberUnknownCallers extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('unknown_callers', function (Blueprint $table) {
			$table->string('caller_number')->after('phone_number')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('unknown_callers', function (Blueprint $table) {
			$table->dropColumn(['caller_number']);
		});
	}
}
