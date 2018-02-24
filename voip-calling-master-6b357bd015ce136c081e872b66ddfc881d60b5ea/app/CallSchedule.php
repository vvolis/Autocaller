<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CallSchedule extends Model
{

	protected $guarded = ['id'];

	protected $casts = [
		'call_start'       => 'datetime',
		'call_end'         => 'datetime',
		'call_reconnects'  => 'integer',
		'call_resets'      => 'integer',
		'call_errors'      => 'integer',
		'credits_expected' => 'integer',
		'credits_real'     => 'integer',
	];

}
