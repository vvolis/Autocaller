<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCallZoneUsesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('call_zone_uses', function (Blueprint $table) {
			$table->increments('id');
			$table->string('phone');
			$table->unsignedInteger('zone');
			$table->date('call_date');
			$table->string('call_phone');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('call_zone_uses');
	}
}
