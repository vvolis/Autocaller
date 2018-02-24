<?php

namespace App\Console\Commands;

use App\CallSchedule;
use App\Libraries\VoipCrawler;
use Illuminate\Console\Command;
use Cache;
use Log;

class GetCredits extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'voip:sys:get_credits';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Get credits from VOIP';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{

		if (Cache::get('sys::settings::advanced-logging')) {
			Log::info('[SYS][CRON][GET-CREDITS]');
		}

		$list = VoipCrawler::getCreditsData();

		$testNumber = '37123115178';
//		dd($list);
		foreach ($list as $apiCredits) {

			$lastCall = CallSchedule::where('phone', '+' . $apiCredits['phone'])->where('call_finished', 1)->whereNotNull('credits_real')->latest()->first();
			$creditsLast = (!is_null($lastCall)) ? $lastCall->credits_real : 0;

			$callNotCredited = CallSchedule::where('phone', '+' . $apiCredits['phone'])->where('call_finished', 1)->whereNull('credits_real')->latest()->first();
//			dd($creditsLast);
//			dd($apiCredits['phone']);
//			if($apiCredits['phone'] == $testNumber){
//				dd('test');
//			}
//			if(!is_null($callNotCredited)){
//				dd($callNotCredited->toArray());
////				dd($callNotCredited);
//			}
//			if (!is_null($callNotCredited)) {
//
////				CallSchedule::where('id', $callNotCredited->id)->update([
////					'credits_real'    => $apiCredits['credits'],
////					'credits_changed' => ($apiCredits['credits'] != $creditsLast) ? true : false,
////				]);
//
//			}
		}

	}

}
