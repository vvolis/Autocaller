<?php

namespace App\Console\Commands;

use App\CallSchedule;
use App\Libraries\Modem;
use Cache;
use App\NumberList;
use Illuminate\Console\Command;
use Log;
use Carbon\Carbon;

class CreateSchedule extends Command
{

	const DAY_TIME_BUFFER = 20;
	const DAY_START_TIME = '19:00:00';
	const DAY_START_INTERVAL_MINUTES = 0;
	const MAX_TOTAL_MINUTES_PER_DAY = 100;
	const MIN_BREAK_MINUTES = 10;
	const MAX_BREAK_MINUTES = 20;
	const MIN_MINUTES_PER_CALL = 2;
	const MAX_MINUTES_PER_CALL = 4;
	const BREAK_EXTEND_OFFSET = 15;

	const FREE_MAX_TOTAL_MINUTES_PER_DAY = 35;
	const FREE_MAX_BUFFER = 10;

	const MIX_MAX_TOTAL_MINUTES_PER_DAY = 45;
	const MIX_MAX_MINUTES_PER_CALL = 15;
	const MIX_MIN_MINUTES_PER_CALL = 9;
	const MIX_MAX_MINUTES_BREAK_PER_CALL = 5;
	const MIX_MIN_MINUTES_BREAK_PER_CALL = 2;
	const MIX_CALL_COUNT = 4;
//	const MIX_CALL_COUNT = 2;
	const MIX_CALL_PART = 2;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'voip:schedule:create';

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
				'from'   => '20:00',
				'to'     => '21:00',
				'rotate' => 1,
			],
			[
				'from'   => '22:00',
				'to'     => '23:00',
				'rotate' => 2,
			],
//			[
//				'from'   => '11:00',
//				'to'     => '12:00',
//				'rotate' => 3,
//			],
		];

	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{

		$carrierOne = 'TELE2';
		$carrierTwo = 'ZAGTEL';

		$createArray = NumberList::whereIn('carrier', [$carrierOne, $carrierTwo])->get()->transform(function ($p) {
			return [
				'phone'   => $p->phone,
				'carrier' => $p->carrier,
			];
		});

		$usbCollection = collect($createArray);

		$phonePool = [];
		$rotations = collect($this->breaks)->count();

		for ($i = 1; $i < ($rotations * self::MIX_CALL_PART) + 1; $i++) {
			$phonePool[$i][$carrierOne] = $usbCollection->where('carrier', $carrierOne)->pluck('phone')->toArray();
			$phonePool[$i][$carrierTwo] = $usbCollection->where('carrier', $carrierTwo)->pluck('phone')->toArray();
		}

		$phoneErrors = 0;
		foreach ($createArray as $usb) {
			$usbRotate = 1;
			foreach (self::generateScheduleBase($usb) as $schedule) {

				$phone = $usb['phone'];
				if (!str_contains($usb['phone'], '+')) {
					$phone = '+' . $phone;
				}

				if ($schedule['action'] == 'outgoing-local') {
					$callRotation = $usbRotate;
					$otherCarrier = ($usb['carrier'] == $carrierOne) ? $carrierTwo : $carrierOne;
					$carrierPhonePool = $phonePool[$callRotation][$otherCarrier];
					if (count($carrierPhonePool) > 0) {
						$randomPhoneFromPool = collect($carrierPhonePool)->random(1)->first();
						$phonePool[$callRotation][$otherCarrier] = array_diff($carrierPhonePool, [$randomPhoneFromPool]);

						//Fix for local phones
//						$randomPhoneFromPool = str_replace('3712', '2', $randomPhoneFromPool);
						$randomPhoneFromPool = str_replace('3712', '+3712', $randomPhoneFromPool);

//						if ($usb['carrier'] == 'BITE21') {
						CallSchedule::create([
							'pool'             => $usb['carrier'],
							'schedule_date'    => Carbon::parse($schedule['call_start'])->toDateString(),
							'call_start'       => Carbon::parse($schedule['call_start'])->toDateTimeString(),
							'call_end'         => Carbon::parse($schedule['call_end'])->toDateTimeString(),
							'call_phone'       => $randomPhoneFromPool,
							'credits_expected' => $schedule['credits_expected'],
							'phone'            => $phone,
						]);
//						}

						$usbRotate++;
					} else {
						$this->error('Phone pool empty, could not find free number - ' . $phoneErrors++);
					}
				}

				if ($schedule['action'] == 'outgoing-global') {
//					if ($usb['carrier'] == 'TELE2') {
//					if ($usb['carrier'] == 'BITE2') {
					CallSchedule::create([
						'pool'             => $usb['carrier'],
						'call_phone'       => Modem::getPhoneDialNumber((int)$usb['phone']),
						'schedule_date'    => Carbon::parse($schedule['call_start'])->toDateString(),
						'call_start'       => Carbon::parse($schedule['call_start'])->toDateTimeString(),
						'call_end'         => Carbon::parse($schedule['call_end'])->toDateTimeString(),
						'credits_expected' => $schedule['credits_expected'],
						'phone'            => $phone,
					]);
//					}
				}

			}
		}
	}

	private function generateScheduleBase(array $usb): array
	{

//		$dateNow = Carbon::now()->addDay(1);
		$dateNow = Carbon::now();
		$startTime = self::DAY_START_TIME;

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

		$total = self::MAX_TOTAL_MINUTES_PER_DAY;

		$breaks = $this->breaks;

//		if ((int)$usb['phone'] == '37123113139')
//			$total = 70;

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
					if ($usb['carrier'] == 'TELE2') {
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

		if (count($freeList) > 0) {

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
//		$breakOffset = (is_null($this->argument('usb'))) ? 0 : self::BREAK_EXTEND_OFFSET;

		return rand(self::MIN_BREAK_MINUTES, self::MAX_BREAK_MINUTES - self::BREAK_EXTEND_OFFSET);
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
