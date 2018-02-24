<?php

namespace App\Console\Commands;

use App\CallSchedule;
use App\PhoneCallCredits;
use App\PhoneCallLogs;
use Cache;
use Carbon\Carbon;
use Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ExtendMissingCredits extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'voip:schedule:missing_credits';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Check for missing credits';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{

		if (Cache::get('sys::settings::advanced-logging')) {
			Log::info('[SYS][CRON][MISSING-CREDITS]');
		}

		$lastLog = PhoneCallLogs::latest()->first();

		$latestCredits = (is_object($lastLog)) ? $lastLog['credits'] : [];

		$createArray = collect(Cache::get('sys::usb_list'))->keyBy('phone')->toArray();

		$nowTime = Carbon::now();

		foreach ($createArray as $usb) {
			$finishedSchedulers = CallSchedule::where('call_finished', false)->where('port', $usb['port']);
			if ($finishedSchedulers->count() == 0) {
				$credits = PhoneCallCredits::where([
					'phone'        => $usb['phone'],
					'credits_date' => $nowTime->toDateString(),
				]);
				if ($credits->count()) {
					$lastCredit = $credits->first();
					$missingCredits = config('voip.daily-credits') + $lastCredit['credits_start'] - $latestCredits[$usb['phone']]['credits'];
					if ($missingCredits > 5) {
						$this->info('[' . $usb['phone'] . '][' . $usb['port'] . '][CMD][SUCCESS][MISSING-CREDITS][MISSING][' . $missingCredits . ']');
						Log::info('[' . $usb['phone'] . '][' . $usb['port'] . '][CMD][SUCCESS][MISSING-CREDITS][MISSING][' . $missingCredits . ']');
						Artisan::call('voip:schedule:create', [
							'date'  => $nowTime->toDateString(),
							'time'  => $nowTime->toTimeString(),
							'limit' => $missingCredits,
							'usb'   => $usb['port'],
						]);
					}
				} else {
					$this->info('[' . $usb['phone'] . '][' . $usb['port'] . '][CMD][SUCCESS][NO CREDITS FOUND TO FETCH]');
					Log::info('[' . $usb['phone'] . '][' . $usb['port'] . '][CMD][SUCCESS][NO CREDITS FOUND TO FETCH]');
				}
			}
		}
}
}
