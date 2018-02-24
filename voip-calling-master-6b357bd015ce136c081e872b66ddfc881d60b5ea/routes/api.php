<?php

use Illuminate\Http\Request;

Route::post('get_number', 'ApiController@getNumber'); //check if needed
Route::post('get_number_stats', 'ApiController@getNumberStats'); //check if needed
Route::post('get_schedule_list', 'ApiController@getSchedule');
Route::post('call_log', 'ApiController@postCallLog');

Route::post('schedule', function () {
//	check_query = "SELECT id as sid, call_phone, call_status, call_reconnects, call_start, call_end \
//						FROM `call_schedules` \
//						WHERE  `call_finished` = 0 AND `phone` = '+{}' LIMIT 1".format(int(phone_number))
});