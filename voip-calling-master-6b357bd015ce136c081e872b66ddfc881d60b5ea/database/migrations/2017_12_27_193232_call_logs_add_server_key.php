<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CallLogsAddServerKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('call_logs', function (Blueprint $table) {
		    $table->string('server_key')->nullable()->after('id');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('call_logs', function (Blueprint $table) {
		    $table->dropColumn(['server_key']);
	    });
    }
}
