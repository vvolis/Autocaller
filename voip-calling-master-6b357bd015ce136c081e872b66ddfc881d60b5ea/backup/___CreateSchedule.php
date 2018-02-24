<?php

namespace App\Console\Commands;

use App\CallSchedule;
use Cache;
use Illuminate\Console\Command;
use Log;
use Carbon\Carbon;

class CreateSchedule2 extends Command
{

	const DAY_TIME_BUFFER = 20;
	const DAY_START_TIME = '07:20:07';
	const DAY_START_INTERVAL_MINUTES = 25;
	const MAX_TOTAL_MINUTES_PER_DAY = 140;
	const MIN_BREAK_MINUTES = 24;
	const MAX_BREAK_MINUTES = 60;
	const MIN_MINUTES_PER_CALL = 9;
	const MAX_MINUTES_PER_CALL = 30;
	const BREAK_EXTEND_OFFSET = 15;

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
		}

		foreach ($createArray as $usb) {
			foreach (self::generateScheduleBase() as $schedule) {
				$phone = $usb['phone'];
				if (!str_contains($usb['phone'], '+')) {
					$phone = '+' . $phone;
				}
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
		$totalScheduleMinutes = 0;

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
			];

			$timeNow->addMinutes($callBreak);
			$totalScheduleMinutes += $callLength;

		endwhile;

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
