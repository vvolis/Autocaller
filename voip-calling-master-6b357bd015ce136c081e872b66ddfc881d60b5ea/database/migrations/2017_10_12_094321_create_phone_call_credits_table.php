<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhoneCallCreditsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('phone_call_credits', function (Blueprint $table) {
			$table->increments('id');
			$table->string('phone');
			$table->date('credits_date');
			$table->unsignedBigInteger('credits_start')->default(0);
			$table->unsignedBigInteger('credits_end')->default(0);
			$table->boolean('connected')->default(false);
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
		Schema::dropIfExists('phone_call_credits');
	}
}
