<?php

namespace App\Console\Commands;

use App\CallSchedule;
use App\Libraries\Modem;
use App\PhoneCallFailLog;
use Carbon\Carbon;
use Cache;
use Log;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunReCaller extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'voip:schedule:recall';

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
			Log::info('[SYS][CRON][RUN-RE-CALLER]');
		}

		$minutesBeforeRecallCheck = 4;

		$timeNow = Carbon::now();

		foreach (collect(Cache::get('sys::usb_list'))->shuffle() as $usb) {

			if (Modem::canUse($usb['port'])) {

				$lastScheduler = CallSchedule::where('call_finished', false)->where('port', $usb['port'])->first();

				if (is_object($lastScheduler) && $lastScheduler->call_status == 1) {

					$callEnd = $lastScheduler->call_end;
					$callEnd->subMinutes($minutesBeforeRecallCheck);

					if ($lastScheduler->call_end > $timeNow->toDateTimeString() && $callEnd > $timeNow) {

						Modem::lock($usb['port']);

						$callReset = false;

//							if ($lastScheduler->call_reconnects >= 3) {
//								$callingNumber = $phoneNumbers[array_rand($phoneNumbers)];
//								$callReset = true;
//							} else {
//								$callingNumber = $lastScheduler->call_phone;
//							}

						$callingNumber = Modem::getPhoneDialNumber($usb['phone']);

						$reDialCommand = sprintf(config('voip.commands.re-dial'), $usb['path'], $callingNumber);

						$process = new Process($reDialCommand);
						$process->start();
						$process->wait();

						if ($process->isSuccessful()) {

							//Log::info('[CMD][SUCCESS][RE-CALL][' . $usb['port'] . '] - Scheduler [#' . $lastScheduler->id . '][' . $reDialCommand . ']');

							$response = null;

							foreach ($process as $type => $data) {
								if ($process::OUT === $type) {
									$response = $data;
								}
							}

							if (str_contains($response, 'ERROR')) {

//								$originalPhoneNumber = $lastScheduler->phone;

								CallSchedule::where('id', $lastScheduler->id)->update([
									'call_phone'      => $callingNumber,
									'call_reconnects' => $callReset ? 0 : $lastScheduler->call_reconnects + 1,
									'call_resets'     => $callReset ? $lastScheduler->call_resets + 1 : $lastScheduler->call_resets,
								]);

								PhoneCallFailLog::create([
									'port'        => $usb['port'],
									'phone'       => $lastScheduler->call_phone,
									'schedule_id' => $lastScheduler->id,
								]);

								Log::info('[' . $lastScheduler->phone . '][' . $usb['port'] . '][CMD][SUCCESS][RE-CALL][RE-ACTIVATED][#' . $lastScheduler->id . '][' . $reDialCommand . ']');

//								if ($callReset) {
//
////									Log::info('[' . $lastScheduler->phone . '][' . $usb['port'] . '][CMD][SUCCESS][RE-CALL][RE-ACTIVATED-REGENERATED][#' . $lastScheduler->id . '][' . $reDialCommand . ']');
////
////									PhoneCallFailLog::create([
////										'port'        => $usb['port'],
////										'phone'       => $originalPhoneNumber,
////										'schedule_id' => $lastScheduler->id,
////									]);
//
//								} else {
//
//									Log::info('[' . $lastScheduler->phone . '][' . $usb['port'] . '][CMD][SUCCESS][RE-CALL][RE-ACTIVATED][#' . $lastScheduler->id . '][' . $reDialCommand . ']');
//								}

							} elseif (str_contains($response, 'OK')) {
								Log::info('[' . $lastScheduler->phone . '][' . $usb['port'] . '][CMD][SUCCESS][RE-CALL][ACTIVE][#' . $lastScheduler->id . '][' . $reDialCommand . ']');
							} else {

								CallSchedule::where('id', $lastScheduler->id)->update([
									'call_errors' => $lastScheduler->call_errors + 1,
								]);

								Log::info('[' . $lastScheduler->phone . '][' . $usb['port'] . '][INFO][ERROR][RE-CALL][COMMUNICATING][#' . $lastScheduler->id . '][' . $reDialCommand . ']');
							}

							sleep(3);

							Modem::unlock($usb['port']);

						} else {

							Modem::unlock($usb['port']);

							Log::info('[' . $lastScheduler->phone . '][' . $usb['port'] . '][CMD][ERROR][RE-CALL][#' . $lastScheduler->id . '][' . $reDialCommand . ']');

						}
					}
				}

			} else {
				if (Cache::get('sys::settings::advanced-logging')) {
					Log::info('[' . $usb['port'] . '][USB][RE-CALLER][BUSY]');
				}
			}

		}

	}

}
