<?php

namespace App\Http\Controllers;

use App\NumberList;
use Artisan;
use App\CallSchedule;
use App\PhoneCallLogs;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class PageController extends Controller
{

	public function callScheduler()
	{

		$calls = CallSchedule::latest()->paginate(40);

		$status = [
			0 => 'pe-7s-stopwatch',
			1 => 'pe-7s-headphones color-wait',
			2 => 'pe-7s-check color-green',
		];

		$doneStatus = [
			0 => 'pe-7s-stopwatch',
			1 => 'pe-7s-check color-green',
		];

		return view('pages.schedule_list', compact('calls', 'status', 'doneStatus'));

	}

	public function callsActive()
	{
		$calls = CallSchedule::where('call_status', 1)->paginate(100);

		$status = [
			0 => 'pe-7s-stopwatch',
			1 => 'pe-7s-headphones color-wait',
			2 => 'pe-7s-check color-green',
		];

		$doneStatus = [
			0 => 'pe-7s-stopwatch',
			1 => 'pe-7s-check color-green',
		];

		return view('pages.schedule_list', compact('calls', 'status', 'doneStatus'));
	}

	public function devices()
	{

//		$devices = Cache::get('sys::credit_list_day');
		$devices = Cache::get('sys::usb_list');

		return view('pages.devices', compact('devices'));

	}

	public function settings()
	{

		$settings = [
			'run_dial'              => Cache::get('sys::settings::run_dial'),
			'run_re-dial'           => Cache::get('sys::settings::run_re-dial'),
			'check-missing-credits' => Cache::get('sys::settings::check-missing-credits'),
			'reset_next-day'        => Cache::get('sys::settings::reset_next-day'),
			'advanced-logging'      => Cache::get('sys::settings::advanced-logging'),
		];

		return view('pages.settings', compact('settings'));

	}

	public function settingsChange($job, $action)
	{

//		flash($action . ' has been <strong>' . ($action == 'enable') ? "Activated" : "Disabled" . '</strong>')->success();

		$actionParsed = ($action === 'enable') ? true : false;

		Cache::forever('sys::settings::' . $job, $actionParsed);

		return back();

	}

	public function settingsDeleteUpcomingSchedules()
	{
//		CallSchedule::where('call_phone')->delete();
//		CallSchedule::whereNull('call_phone')->delete();
		CallSchedule::where('extended', true)->where('call_status', 1)->update([
			'call_end' => Carbon::now()->toDateTimeString(),
		]);
		CallSchedule::where('extended', true)->where('call_status', 0)->delete();

		return back();
	}

	public function settingsGetDailyLogs($date)
	{

		$tailCommand = 'tail -f -n1000 ' . base_path() . '/storage/logs/laravel-%s.log';

		$process = new Process(sprintf($tailCommand, $date));
		$output = '';
		$process->setTimeout(null)->run(function ($type, $line) use ($process, &$output) {
			$output = $line;
			$process->stop(3);
		});

		$output = str_replace('  ', '<br />', $output);

		return $output;

	}

	public function settingsSystemLogs()
	{

		$process = new Process('dmesg -T | tail -50');
		$output = '';
		$process->setTimeout(null)->run(function ($type, $line) use ($process, &$output) {
			$output = $line;
			$process->stop(3);
		});

		$output = str_replace('[', '<br />[', $output);
//		$output = str_replace("\n\n", "<br />", $output);
//		$output = str_replace("\n", "", $output);
//		dd($output);
		return $output;

	}

	public function settingsCheckModems()
	{

		Artisan::call('voip:sys:debug_usb_numbers');

		return back();

	}

	public function settingsReadUSBs()
	{

		Artisan::call('voip:sys:usb_read');

		return back();

	}

	public function creteUSBSchedule()
	{
//		Artisan::call('voip:schedule:create', [
//			'date'  => $nowTime->toDateString(),
//			'time'  => $nowTime->toTimeString(),
//			'limit' => $missingCredits,
//			'usb'   => $usb['port'],
//		]);
	}

	public function dashboard()
	{

		$calls = CallSchedule::where('extended', true)->where('call_finished', 0)->latest()->get();

		return view('pages.dashboard', compact('calls'));

	}

	public function showTestGenerator()
	{

		$callPools = collect(NumberList::get()->pluck('carrier', 'carrier'))->unique();

		$callPhonePool = NumberList::get();

		$calls = CallSchedule::where('extended', true)->latest()->paginate(30);

		$status = [
			0 => 'pe-7s-stopwatch',
			1 => 'pe-7s-headphones color-wait',
			2 => 'pe-7s-check color-green',
		];

		$doneStatus = [
			0 => 'pe-7s-stopwatch',
			1 => 'pe-7s-check color-green',
		];

		return view('generate_credits', compact('calls', 'status', 'doneStatus', 'callPools', 'callPhonePool'));

	}

	public function postTestGroup(Request $request)
	{

		$carrier = $request->input('carrier', null);

		if (!is_null($carrier)) {
			$this->generatePhoneCall('group', $request);
		}

		return redirect()->back();

	}

	public function postTestSingle(Request $request)
	{

		$phone = $request->input('single_number', null);

		if (!is_null($phone)) {
			$this->generatePhoneCall('single', $request);
		}

		return redirect()->back();

	}

	private function generatePhoneCall($type, $request)
	{

		$numbers = [];
		$postNumbers = explode("\n", $request->input('number_list', null));
		foreach ($postNumbers as $number) {
			if (strlen((int)$number) > 5) {
				$numbers[] = '+' . (int)$number;
			}
		}

		if ($type == 'single') {
			$carrierQuery = NumberList::where('phone', $request->input('single_number'))->first();
			$carrier = $carrierQuery->carrier;

			$pool = [];
			$pool[$carrier] = [];
			$pool[$carrier]['numbers'] = [$request->input('single_number')];
		} else {
			$carrier = $request->input('carrier', null);
			$pool = [];
			$pool[$carrier] = [];
			$pool[$carrier]['numbers'] = NumberList::where('carrier', $carrier)->pluck('phone')->toArray();
		}

		$pool[$carrier]['test_numbers'] = $numbers;

		foreach ($pool as $carrierName => $carrierValues) {

			$carrierPhonePool = $carrierValues['test_numbers'];
			$carrierFromPool = $carrierValues['numbers'];
			$carrierFromIndex = 0;
			$carrierFromPoolTimes = [];

			while (count($carrierPhonePool) > 0) :

				$randomPhoneFromPool = collect($carrierPhonePool)->random(1)->first();
				$carrierPhonePool = array_diff($carrierPhonePool, [$randomPhoneFromPool]);

				if ($carrierFromIndex == count($carrierFromPool) - 1) {
					$carrierFromIndex = 0;
				} else {
					$carrierFromIndex++;
				}
				
				$fromNumber = $carrierFromPool[$carrierFromIndex];

				if (isset($carrierFromPoolTimes[$fromNumber])) {
					$startTime = Carbon::parse($carrierFromPoolTimes[$fromNumber]);
				} else {
					$carrierFromPoolTimes[$fromNumber] = Carbon::now();
					$startTime = Carbon::now();
				}

				$callLength = (int)$request->input('call_length', 3);
				$breakLength = (int)$request->input('break_length', 5);

				$startTime = $startTime->copy()->addMinutes(1);
				$endTime = $startTime->copy()->addMinutes($callLength);

				$carrierFromPoolTimes[$fromNumber] = $endTime->copy()->addMinutes($breakLength);

				CallSchedule::create([
					'pool'             => $carrier,
					'schedule_date'    => $startTime->toDateString(),
					'call_phone'       => $randomPhoneFromPool,
					'call_start'       => $startTime->toDateTimeString(),
					'call_end'         => $endTime->toDateTimeString(),
					'credits_expected' => $callLength,
					'phone'            => '+' . $fromNumber,
					'extended'         => true,
				]);

			endwhile;

		}

	}

	public function credits()
	{

		$stats = [
			'points_start'  => 0,
			'points_limit'  => 0,
			'points_earned' => 0,
		];

		$devices = Cache::get('sys::usb_list');
		$creditsCurrent = [];
		$devicesCreditsLast = PhoneCallLogs::latest()->first();
		if (is_object($devicesCreditsLast)) {
			$creditsCurrent = $devicesCreditsLast->credits;
		}
//		dd([$devices, $creditsCurrent]);
//		dd($devices);
		foreach ($devices as $device) {
			if (isset($creditsCurrent[$device['phone']])) {
				$stats['points_start'] += $device['credits_start'];
				$stats['points_limit'] += $device['credits_limit'];
				$stats['points_earned'] += $creditsCurrent[$device['phone']]['credits'];
			}
		}

		$stats['total_to_earn'] = $stats['points_limit'] - $stats['points_start'];
		$stats['total_to_earned'] = $stats['points_limit'] - $stats['points_earned'];
		$stats['stats_percent_earned'] = (int)round($stats['total_to_earned'] / $stats['total_to_earn'] * 100);

		$statsChart = [
			'session' => [
				'labels' => [
					'(' . ($stats['total_to_earn'] - $stats['total_to_earned']) . ') ' . (100 - $stats['stats_percent_earned']) . '%',
					'(' . $stats['total_to_earned'] . ') ' . $stats['stats_percent_earned'] . '%',
				],
				'series' => [
					100 - $stats['stats_percent_earned'],
					$stats['stats_percent_earned'],
				],
			],
		];

//		dd($statsChart['session']['labels']);

//		dd($statsChart);
//		dd($stats);
//		dd($devices);
//		dd($creditsCurrent);

		return view('pages.credits', compact('devices', 'stats', 'statsChart', 'devicesCreditsLast', 'creditsCurrent'));

	}

	public function processImportExcel()
	{

		$inserted = 0;
		if (request()->has('file')) {

			$file = request()->file('file');
			$newFileName = str_random(10) . '.' . $file->getClientOriginalExtension();

			$file->move('files', $newFileName);

			$fileExcel = public_path('files/' . $newFileName);

			\Excel::selectSheetsByIndex(0)->load($fileExcel, function ($reader) use (&$inserted) {

				foreach ($reader->get()->toArray() as $row) {

					if (isset($row['from']) &&
						isset($row['to']) &&
						isset($row['start']) &&
						isset($row['end']) &&
						(int)$row['from'] != 0 &&
						(int)$row['to'] != 0) {

						$inserted++;
						$phoneQuery = NumberList::where('phone', $row['from'])->first();
						$pool = (!is_null($phoneQuery)) ? $phoneQuery->carrier : '';

						CallSchedule::create([
							'pool'          => $pool,
							'phone'         => '+' . (int)$row['from'],
							'call_phone'    => '+' . (int)$row['to'],
							'schedule_date' => $row['start']->toDateString(),
							'call_start'    => $row['start']->toDateTimeString(),
							'call_end'      => $row['end']->toDateTimeString(),
						]);

					}
				}

			});

		}

		return redirect('1234')->with('status', 'Inserted ' . $inserted . ' records');

	}

}
