<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhoneCallLogs extends Model
{

	protected $guarded = ['id'];

	protected $casts = [
		'credits' => 'json',
	];

}
