<?php

namespace App\Console\Commands;

use App\CallSchedule;
use App\Libraries\Modem;
use Cache;
use Illuminate\Console\Command;
use Log;
use Carbon\Carbon;

class CreateSchedule2 extends Command
{

	const DAY_TIME_BUFFER = 20;
	const DAY_START_TIME = '08:00:00';
	const DAY_START_INTERVAL_MINUTES = 25;
	const MAX_TOTAL_MINUTES_PER_DAY = 110;
	const MIN_BREAK_MINUTES = 8;
	const MAX_BREAK_MINUTES = 30;
	const MIN_MINUTES_PER_CALL = 6;
	const MAX_MINUTES_PER_CALL = 18;
	const BREAK_EXTEND_OFFSET = 15;

	const FREE_MAX_TOTAL_MINUTES_PER_DAY = 35;
	const FREE_MAX_BUFFER = 10;

	const MIX_MAX_TOTAL_MINUTES_PER_DAY = 60;
	const MIX_MAX_MINUTES_PER_CALL = 15;
	const MIX_MIN_MINUTES_PER_CALL = 9;
	const MIX_MAX_MINUTES_BREAK_PER_CALL = 5;
	const MIX_MIN_MINUTES_BREAK_PER_CALL = 2;
	const MIX_CALL_COUNT = 4;
	const MIX_CALL_PART = 2;

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

	protected $breaks = [];

	protected $phones = [];

	public function __construct()
	{
		parent::__construct();

		$this->breaks = [
			[
				'from'   => '09:00',
				'to'     => '10:00',
				'rotate' => 1,
			],
			[
				'from'   => '11:00',
				'to'     => '12:00',
				'rotate' => 2,
			],
			[
				'from'   => '13:00',
				'to'     => '14:00',
				'rotate' => 3,
			],
		];

		$this->phones = [
			'BITE1' => [
				'local' => false,
				'list'  => [
					'37123113135',
					'37123113136',
					'37123113137',
					'37123113138',
					'37123113139',
				],
			],
			'BITE2' => [
				'local' => false,
				'list'  => [
					'37123115177',
					'37123115178',
					'37123115179',
					'37123115180',
					'37123115181',
				],
			],
			'TELE2' => [
				'local' => false,
				'list'  => [
					'37127098366',
					'37120378758',
					'37125905914',
					'37120363044',
				],
			],
		];
	}

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

		}

		$usbCollection = collect($createArray);

		$phonePool = [];
		$rotations = collect($this->breaks)->count();
		for ($i = 1; $i < ($rotations * self::MIX_CALL_PART) + 1; $i++) {
			$phonePool[$i]['BITE2'] = $usbCollection->where('carrier', 'BITE2')->pluck('phone')->toArray();
			$phonePool[$i]['BITE1'] = $usbCollection->where('carrier', 'BITE1')->pluck('phone')->toArray();
			if (count($phonePool[$i]['TELE2']) != count($phonePool[$i]['BITE1'])) {
				dd('Phone pool not equal');
			}
		}

		$phoneErrors = 0;
		foreach ($createArray as $usb) {
			$usbRotate = 1;
			foreach (self::generateScheduleBase($usb) as $schedule) {
				$extended = (is_null($this->argument('usb'))) ? false : true;

				$phone = $usb['phone'];
				if (!str_contains($usb['phone'], '+')) {
					$phone = '+' . $phone;
				}

				if ($schedule['action'] == 'outgoing-local') {
					$callRotation = $usbRotate;
					$otherCarrier = ($usb['carrier'] == 'BITE1') ? 'TELE2' : 'BITE1';
					$carrierPhonePool = $phonePool[$callRotation][$otherCarrier];
					if (count($carrierPhonePool) > 0) {
						$randomPhoneFromPool = collect($carrierPhonePool)->random(1)->first();
						$phonePool[$callRotation][$otherCarrier] = array_diff($carrierPhonePool, [$randomPhoneFromPool]);

						//Fix for local phones
						$randomPhoneFromPool = str_replace('3712', '2', $randomPhoneFromPool);

						CallSchedule::create([
							'port'             => $usb['port'],
							'schedule_date'    => Carbon::parse($schedule['call_start'])->toDateString(),
							'call_start'       => Carbon::parse($schedule['call_start'])->toDateTimeString(),
							'call_end'         => Carbon::parse($schedule['call_end'])->toDateTimeString(),
							'call_phone'       => $randomPhoneFromPool,
							'credits_expected' => $schedule['credits_expected'],
							'phone'            => $phone,
						]);

						$usbRotate++;
					} else {
						$this->error('Phone pool empty, could not find free number - ' . $phoneErrors++);
					}
				}

				if ($schedule['action'] == 'outgoing-global') {
					if ($usb['carrier'] != 'TELE2') {
						CallSchedule::create([
							'port'             => $usb['port'],
							'schedule_date'    => Carbon::parse($schedule['call_start'])->toDateString(),
							'call_start'       => Carbon::parse($schedule['call_start'])->toDateTimeString(),
							'call_end'         => Carbon::parse($schedule['call_end'])->toDateTimeString(),
							'credits_expected' => $schedule['credits_expected'],
							'phone'            => $phone,
							'extended'         => $extended,
						]);
					}
				}

			}
		}
	}

	private function generateScheduleBase(array $usb): array
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

		return $this->generatePhoneScheduler($schedulerStart, $usb);

	}

	private function generatePhoneScheduler($timeNow, array $usb): array
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

		$breaks = $this->breaks;

		while ($totalScheduleMinutes <= $total) :

			$callTime = $timeNow;
			$callBreak = $this->getCallBreak();
			$callLength = $this->getCallLength();

			$checkBetween = $this->checkIfEndBetweenBreaks($breaks, $callTime->format('H:i'));

			if ($checkBetween['found']) {
				$mixTime = Carbon::parse($callTime->toDateString() . ' ' . $checkBetween['from']);
				$mixTime = $mixTime->addMinutes(rand(1, 5)); // 50 buffer

				for ($i = 1; $i <= self::MIX_CALL_COUNT; $i++) {

					$mixCallLength = rand(self::MIX_MIN_MINUTES_PER_CALL, self::MIX_MAX_MINUTES_PER_CALL);
					$mixCallBreak = rand(self::MIX_MIN_MINUTES_BREAK_PER_CALL, self::MIX_MAX_MINUTES_BREAK_PER_CALL);
					if ($usb['carrier'] == 'BITE1') {
						$style = ($i % self::MIX_CALL_PART == 0) ? 'outgoing-local' : 'waiting-local';
					} else {
						$style = ($i % self::MIX_CALL_PART == 0) ? 'waiting-local' : 'outgoing-local';
					}

					$callList[] = [
						'call_start'       => $mixTime->toDateTimeString(),
						'call_end'         => $mixTime->addMinutes($mixCallLength)->toDateTimeString(),
						'credits_expected' => $callLength,
						'break'            => 1,
						'action'           => $style,
					];
					$mixTime->addMinutes($mixCallBreak);
				}
				$callTime->addMinutes(70);

			} else {

				$callStart = $callTime;
				$callEnd = $callTime->copy()->addMinutes($callLength);

				$startBetween = $this->checkIfEndBetweenBreaks($breaks, $callStart->format('H:i'));
				$endBetween = $this->checkIfEndBetweenBreaks($breaks, $callEnd->format('H:i'));

				if (!$startBetween && !$endBetween) {
					$callList[] = [
						'call_start'       => $callStart->toDateTimeString(),
						'call_end'         => $callEnd->toDateTimeString(),
						'credits_expected' => $callLength,
						'break'            => 0,
						'action'           => 'outgoing-global',
					];
				}

//				$freeList[] = ['call_start' => $callTime->copy()->addMinutes(2)];

				$timeNow->addMinutes($callBreak);
				$totalScheduleMinutes += $callLength;
			}

		endwhile;

//		dd($callList);

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

	private function checkIfEndBetweenBreaks($breaks, $time)
	{
		foreach ($breaks as $break) {
			if ($time >= $break['from'] && $time <= $break['to']) {
				return [
					'from'  => $break['from'],
					'to'    => $break['to'],
					'found' => true,
				];
			}
		}

		return false;
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
