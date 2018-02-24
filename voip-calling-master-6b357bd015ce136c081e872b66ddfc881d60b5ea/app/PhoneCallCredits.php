<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhoneCallCredits extends Model
{

	protected $guarded = ['id'];

	protected $casts = [
//		'phone'         => 'integer',
		'credits_start' => 'integer',
		'credits_end'   => 'integer',
		'credits_date'  => 'date',
		'connected'     => 'boolean',
	];

}
