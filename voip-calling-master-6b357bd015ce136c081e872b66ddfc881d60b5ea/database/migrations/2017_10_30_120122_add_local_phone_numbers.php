<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLocalPhoneNumbers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('number_lists', function (Blueprint $table) {

		    $table->boolean('local')->default(false)->after('carrier');

	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('number_lists', function (Blueprint $table) {

		    $table->dropColumn(['local']);

	    });
    }
}
