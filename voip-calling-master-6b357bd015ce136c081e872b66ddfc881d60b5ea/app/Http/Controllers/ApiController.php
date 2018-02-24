<?php

namespace App\Http\Controllers;

use App\CallLogs;
use App\CallSchedule;
use App\Libraries\Modem;
use App\ModemStatus;
use Carbon\Carbon;
use DB;
use Log;
use Illuminate\Http\Request;

class ApiController extends Controller
{

	public function getSchedule(Request $request)
	{

		if (!$this->validateServerKey($request)) {
			return response()->json([
				'status' => 'invalid_server_key',
				'data'   => null,
			]);
		}

		$phone = $request->input('phone_number');

		$result = [
			'status' => 'success',
			'debug'  => $request->all(),
			'data'   => CallSchedule::orderBy('call_start')->where('phone', '+' . $phone)->where('call_finished', 0)->first(),
		];

		return response()->json($result);

	}

	public function postCallLog(Request $request)
	{

		if (!$this->validateServerKey($request)) {
			return response()->json([
				'status' => 'invalid_server_key',
				'data'   => null,
			]);
		}

		\Log::info('DEBUG REQUEST: ' . print_r($request->all(), true));

		$callType = $request->input('action');
		$callId = $request->input('call_id', null);

		if ($callType == 'call_start') {
			CallSchedule::where('id', $callId)->update([
				'call_status' => 1,
			]);
		}

		if (!is_null($request->input('call_type', null)) == 'call_reconnect') {
			if (!is_null($callId)) {
				$callQuery = CallSchedule::where('id', $callId)->first();
				if (!is_object($callQuery)) {
					if ($callQuery->call_reconnects > config('voip.voip-reconnect-fails')) {
						CallSchedule::where('id', $callId)->update([
							'call_status'     => 2,
							'call_finished'   => 1,
							'call_errors'     => 1,
							'call_reconnects' => DB::raw('call_reconnects+1'),
						]);
					} else {
						CallSchedule::where('id', $callId)->update([
							'call_status'     => 0,
							'call_reconnects' => DB::raw('call_reconnects+1'),
						]);
					}
				}
			}
		}

		CallLogs::create([
			'server_key'           => config('voip.server_keys')[$request->input('server_key')],
			'call_id'              => $request->input('call_id', null),
			'action'               => $request->input('action', null),
			'created_at'           => Carbon::now()->toDateTimeString(),
			'phone_number'         => $request->input('phone_number', null),
			'call_number'          => $request->input('call_number', null),
			'call_incoming_number' => $request->input('incoming_number', null),
		]);

		if (ModemStatus::whereDate('event_date', Carbon::now()->toDateString())->first()) {
			ModemStatus::where('number', $request->input('phone_number', null))
				->whereDate('event_date', Carbon::now()->toDateString())
				->update([
					'events_count_success' => \DB::raw('events_count_success + 1'),
					'event_last_success'   => Carbon::now()->toDateTimeString(),
				]);
		} else {
			ModemStatus::create([
				'number'             => $request->input('phone_number', null),
				'event_date'         => Carbon::now()->toDateString(),
				'event_last_success' => Carbon::now()->toDateTimeString(),
			]);
		}

		if ($callType == 'call_hang_up') {
			CallSchedule::where('id', $callId)->update([
				'call_finished' => 1,
				'call_status'   => 2,
			]);
		}

		return response()->json([
			'status' => 'success',
		]);

	}

	public function getNumberStats(Request $request)
	{

		if (!$this->validateServerKey($request)) {
			return response()->json([
				'status' => 'invalid_server_key',
				'data'   => null,
			]);
		}

		$response = [
			'data'   => [
				'carrier' => Modem::getPhoneCarrier((int)$request->input('phone_number', null)),
			],
			'debug'  => $request->all(),
			'status' => 'success',
		];

		return response()->json($response);
	}

	public function getNumber(Request $request)
	{

		if (!$this->validateServerKey($request)) {
			return response()->json([
				'status' => 'invalid_server_key',
				'data'   => null,
			]);
		}

		$response = ['status' => 'error'];
		$phoneNumber = $request->input('phone_number');

		$res = Modem::getPhoneDialNumber($phoneNumber);
		if (!is_null($res)) {
			$response = ['status' => 'success', 'phone' => $res];
		}

		return response()->json($response);

	}

	private function validateServerKey($request)
	{

		$serverKey = $request->input('server_key', null);
		if (!is_null($serverKey)) {
			if (array_key_exists($serverKey, config('voip.server_keys'))) {
				return true;
			}
		}

		return false;
	}

}
