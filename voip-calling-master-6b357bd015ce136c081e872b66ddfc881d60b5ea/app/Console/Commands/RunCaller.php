<?php

namespace App\Console\Commands;

use App\CallSchedule;
use App\Libraries\Modem;
use Carbon\Carbon;
use Cache;
use Log;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunCaller extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'voip:schedule:call';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Run schedule worker';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{

		if (Cache::get('sys::settings::advanced-logging')) {
			Log::info('[SYS][CRON][RUN-CALLER]');
		}

		$timeNow = Carbon::now();

		foreach (collect(Cache::get('sys::usb_list'))->shuffle() as $usb) {

			if (Modem::canUse($usb['port'])) {

				$lastScheduler = CallSchedule::where('call_finished', false)->where('port', $usb['port'])->first();

				if (is_object($lastScheduler)) {

					if ($lastScheduler->call_status == 0 && $lastScheduler->call_start < $timeNow->toDateTimeString()) {

						Modem::lock($usb['port']);

						$callingNumber = Modem::getPhoneDialNumber($usb['phone']);

						$dialCommand = sprintf(config('voip.commands.dial'), $usb['path'], $callingNumber);

						$process = new Process($dialCommand);
						$process->run();

						if ($process->isSuccessful()) {
							Log::info('[' . $lastScheduler->phone . '][' . $usb['port'] . '][CMD][SUCCESS][CALL][#' . $lastScheduler->id . '][' . $dialCommand . ']');
						} else {
							Log::critical('[' . $lastScheduler->phone . '][' . $usb['port'] . '][CMD][ERROR][CALL][#' . $lastScheduler->id . '][' . $dialCommand . ']');
						}

						sleep(4);

						CallSchedule::where('id', $lastScheduler->id)->update([
							'call_start'  => $timeNow->toDateTimeString(),
							'call_phone'  => $callingNumber,
							'call_status' => 1,
						]);

						Modem::unlock($usb['port']);

					}

					if ($lastScheduler->call_status == 1 && $lastScheduler->call_end < $timeNow->toDateTimeString()) {

						Modem::lock($usb['port']);

						$hangUpCommand = sprintf(config('voip.commands.hang-up'), $usb['path']);
						$process = new Process($hangUpCommand);
						$process->run();

						if ($process->isSuccessful()) {
							Log::info('[' . $lastScheduler->phone . '][' . $usb['port'] . '][CMD][SUCCESS][HANG-UP][#' . $lastScheduler->id . '][' . $hangUpCommand . ']');
						} else {
							Log::critical('[' . $lastScheduler->phone . '][' . $usb['port'] . '][CMD][ERROR][HANG-UP][#' . $lastScheduler->id . '][' . $hangUpCommand . ']');
						}

						sleep(4);

						CallSchedule::where('id', $lastScheduler->id)->update([
							'call_end'      => $timeNow->toDateTimeString(),
							'call_status'   => 2,
							'call_finished' => true,
						]);

						Modem::unlock($usb['port']);

					}

				}

			} else {
				if (Cache::get('sys::settings::advanced-logging')) {
					Log::info('[' . $usb['port'] . '][USB][CALLER][BUSY]');
				}
			}
		}
	}

	function shuffle_assoc(&$array)
	{
		$keys = array_keys($array);

		shuffle($keys);

		foreach ($keys as $key) {
			$new[$key] = $array[$key];
		}

		$array = $new;

		return true;
	}
}
