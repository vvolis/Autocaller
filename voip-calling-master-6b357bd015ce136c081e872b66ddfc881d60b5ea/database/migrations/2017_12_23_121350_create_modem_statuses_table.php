<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModemStatusesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('modem_statuses', function (Blueprint $table) {
			$table->increments('id');
			$table->string('number')->nullable();
			$table->integer('events_count_success')->default(0);
			$table->integer('events_count_errors')->default(0);
			$table->date('event_date');
			$table->dateTime('event_last_success');
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
		Schema::dropIfExists('modem_statuses');
	}
}
