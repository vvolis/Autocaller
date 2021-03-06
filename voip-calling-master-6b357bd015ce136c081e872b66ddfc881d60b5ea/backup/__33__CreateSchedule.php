<?php

namespace App\Console\Commands;

use App\CallSchedule;
use App\Libraries\Modem;
use Cache;
use Illuminate\Console\Command;
use Log;
use Carbon\Carbon;

class CreateSchedule extends Command
{

	const DAY_TIME_BUFFER = 20;
	const DAY_START_TIME = '13:55:00';
	const DAY_START_INTERVAL_MINUTES = 10;
	const MAX_TOTAL_MINUTES_PER_DAY = 250;
	const MIN_BREAK_MINUTES = 10;
	const MAX_BREAK_MINUTES = 15;
	const MIN_MINUTES_PER_CALL = 18;
	const MAX_MINUTES_PER_CALL = 25;
	const BREAK_EXTEND_OFFSET = 15;

	const FREE_MAX_TOTAL_MINUTES_PER_DAY = 120;
	const FREE_MAX_BUFFER = 10;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'voip:schedule:create {date?} {time?} {limit?} {usb?}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create schedule';


	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{

		if (Cache::get('sys::settings::advanced-logging')) {
			Log::info('[SYS][CRON][CREATE-SCHEDULER]');
		}

		$usbArray = Cache::get('sys::usb_list');


		if (!is_null($this->argument('usb'))) {

			$portCollection = collect($usbArray)->keyBy('port')->toArray();

			if (!isset($portCollection[$this->argument('usb')]['phone'])) {
				dd('phone missing');
			}

			$createArray[] = [
				'call_start' => $this->argument('date'),
				'phone'      => $portCollection[$this->argument('usb')]['phone'],
				'port'       => $this->argument('usb'),
			];

		} else {

			$createArray = Cache::get('sys::usb_list');
//
//			$usbArray = Cache::get('sys::usb_list');
//>>>>>>> master
//			$usbArray = collect($usbArray);

//			if (Cache::has('daily_number')) {
//				dd('has');
//				$dailyNumber = Cache::get('daily_number');
//				if ($dailyNumber['date'] == Carbon::now()->toDateTimeString()) {
//					$createArray = $usbArray->whereIn('phone', $dailyNumber['numbers'])->toArray();
//				} else {
//					$createArray = $usbArray->random(20);
//					Cache::forever('daily_number', [
//						'date'    => Carbon::now()->toDateTimeString(),
//						'numbers' => $createArray->pluck('numbers'),
//					]);
//				}
//			} else {
//
//				$createArray = $usbArray->where('local', false)->random(20);
//				dd($createArray);
//				Cache::forever('daily_number', [
//					'date'    => Carbon::now()->toDateTimeString(),
//					'numbers' => $createArray->pluck('phone')->toArray(),
//				]);
//				$createArray = $createArray->toArray();
//			}
		}

//		$mixNumbers = collect($usbArray)->where('local', true)->toArray();
//		$createArray = array_merge($createArray, $mixNumbers);

		foreach ($createArray as $usb) {
			foreach (self::generateScheduleBase() as $schedule) {

				$phone = $usb['phone'];
				if (!str_contains($usb['phone'], '+')) {
					$phone = '+' . $phone;
				}
				if ($schedule['break'] == 0 && !$usb['local']) {
					CallSchedule::create([
						'port'             => $usb['port'],
						'schedule_date'    => Carbon::parse($schedule['call_start'])->toDateString(),
						'call_start'       => Carbon::parse($schedule['call_start'])->toDateTimeString(),
						'call_end'         => Carbon::parse($schedule['call_end'])->toDateTimeString(),
						'credits_expected' => $schedule['credits_expected'],
						'phone'            => $phone,
						'extended'         => (is_null($this->argument('usb'))) ? false : true,
					]);
				}

				if ($schedule['break'] == 1) {
					$createArray = Cache::get('sys::usb_list');

					$fromPhone = null;
					$found = false;
					$tries = 0;
					$maxTries = 8;
					while ($tries <= $maxTries || $found == true) :
						$usbArrayNew = collect($createArray)->where('local', true)->random(1);
						$usbArrayNew = $usbArrayNew->toArray();
						$usbRandom = $usbArrayNew[0];
						$check = Modem::localPhoneFree($usbRandom['phone'], $schedule['call_start'], $schedule['call_end']);
						if ($check == true) {
							$found == true;
							$fromPhone = $usbRandom['phone'];
						}
						$tries++;
					endwhile;

					if (!is_null($fromPhone)) {
						CallSchedule::create([
							'port'             => $usbRandom['port'],
							'schedule_date'    => Carbon::parse($schedule['call_start'])->toDateString(),
							'call_phone'       => str_replace('371', '', $usb['phone']),
							'call_start'       => Carbon::parse($schedule['call_start'])->toDateTimeString(),
							'call_end'         => Carbon::parse($schedule['call_end'])->toDateTimeString(),
							'phone'            => '+' . $fromPhone,
							'credits_expected' => $schedule['credits_expected'],
							'break'            => $schedule['break'],
							'extended'         => (is_null($this->argument('usb'))) ? false : true,
						]);
					} else {
						Log::info('Could not found free number');
					}

				}
			}
		}

	}

	private function generateScheduleBase(): array
	{

		if (!is_null($this->argument('date'))) {
			$dateNow = Carbon::parse($this->argument('date'));
		} else {
			$dateNow = Carbon::now();
		}

		if (!is_null($this->argument('time'))) {
			$startTime = $this->argument('time');
		} else {
			$startTime = self::DAY_START_TIME;;
		}

		$schedulerStart = $dateNow->format('Y-m-d') . ' ' . $startTime;
		$schedulerStart = Carbon::parse(($schedulerStart));

		$schedulerStart->addMinutes($this->getStartInterval());

		return $this->generatePhoneScheduler($schedulerStart);

	}

	private function generatePhoneScheduler($timeNow): array
	{

		$callList = [];
		$freeList = [];
		$totalScheduleMinutes = 0;
		$totalFreeMinutes = 0;

		$limit = $this->argument('limit');
		if (!is_null($limit)) {
			if ($limit < 5) {
				$limit = 5;
			}
			$total = $limit + self::DAY_TIME_BUFFER;
		} else {
			$total = self::MAX_TOTAL_MINUTES_PER_DAY;
		}

		while ($totalScheduleMinutes <= $total) :

			$callTime = $timeNow;
			$callBreak = $this->getCallBreak();
			$callLength = $this->getCallLength();

			$callList[] = [
				'call_start'       => $callTime->toDateTimeString(),
				'call_end'         => $callTime->addMinutes($callLength)->toDateTimeString(),
				'credits_expected' => $callLength,
				'break'            => 0,
			];

			$freeList[] = ['call_start' => $callTime->copy()->addMinutes(2)];

			$timeNow->addMinutes($callBreak);
			$totalScheduleMinutes += $callLength;

		endwhile;

		if (is_null($limit) && count($freeList) > 0) {

			$totalFree = self::FREE_MAX_TOTAL_MINUTES_PER_DAY;

			while ($totalFreeMinutes <= $totalFree) :

				foreach ($freeList as $free) :

					$breakMinutes = rand(5, 15);
					$breakStart = $free['call_start'];
					$breakEnd = $breakStart->copy()->addMinutes($breakMinutes);

					$callList[] = [
						'call_start'       => $breakStart->toDateTimeString(),
						'call_end'         => $breakEnd->toDateTimeString(),
						'credits_expected' => $breakMinutes,
						'break'            => 1,
					];

					$totalFreeMinutes += $breakMinutes;

				endforeach;

			endwhile;

		}


		return $callList;

	}

	private function getCallBreak(): int
	{
		$breakOffset = (is_null($this->argument('usb'))) ? 0 : self::BREAK_EXTEND_OFFSET;

		return rand(self::MIN_BREAK_MINUTES, self::MAX_BREAK_MINUTES - $breakOffset);
	}

	private function getCallLength(): int
	{
		return rand(self::MIN_MINUTES_PER_CALL, self::MAX_MINUTES_PER_CALL);
	}

	private function getStartInterval(): int
	{
		return rand(3, self::DAY_START_INTERVAL_MINUTES);
	}

}
