<?php

Route::get('1234', 'PageController@showTestGenerator')->name('call_generator');
Route::post('1234', 'PageController@postTestGroup')->name('postTest');
Route::post('1234_single', 'PageController@postTestSingle')->name('postTestSingle');
Route::get('settings', 'PageController@settings');
Route::get('settings/delete_schedules', 'PageController@settingsDeleteUpcomingSchedules');
Route::get('calls', 'PageController@callScheduler');
Route::get('calls_active', 'PageController@callsActive');
Route::post('import_excel', 'PageController@processImportExcel')->name('import_excel');

Route::group(['middleware' => 'auth'], function () {
//	Route::get('/', 'PageController@dashboard');
//	Route::get('calls', 'PageController@callScheduler');
//	Route::get('devices', 'PageController@devices');
//	Route::get('credits', 'PageController@credits');
//	Route::get('settings', 'PageController@settings');
//	Route::get('settings/job/{job}/{action?}', 'PageController@settingsChange');
//	Route::get('settings/delete_schedules', 'PageController@settingsDeleteUpcomingSchedules');
//	Route::get('settings/check_modems', 'PageController@settingsCheckModems');
//	Route::get('settings/read_usb', 'PageController@settingsReadUSBs');
//	Route::get('settings/get_daily_logs/{date}', 'PageController@settingsGetDailyLogs');
//	Route::get('settings/get_system_logs', 'PageController@settingsSystemLogs');
});

Route::get('login', 'Auth\LoginController@showLoginForm');
Route::post('login', 'Auth\LoginController@login')->name('login');
Route::get('logout', 'Auth\LoginController@logout')->name('logout');
Route::get('reg1338', 'Auth\RegisterController@showRegistrationForm');
Route::post('reg1338', 'Auth\RegisterController@register')->name('register');
Route::get('api/test', 'ApiController@index');
Route::get('test1', function () {
	\App\Libraries\SendSMS::sendStats();
});