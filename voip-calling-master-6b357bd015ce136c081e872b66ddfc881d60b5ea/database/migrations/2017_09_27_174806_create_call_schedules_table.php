<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCallSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('port')->index();
            $table->string('phone')->nullable();
            $table->unsignedSmallInteger('status')->default(0);
            $table->boolean('done')->default(false);
            $table->date('schedule_date');
            $table->dateTime('call_start');
            $table->dateTime('call_end');
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
        Schema::dropIfExists('call_schedules');
    }
}
