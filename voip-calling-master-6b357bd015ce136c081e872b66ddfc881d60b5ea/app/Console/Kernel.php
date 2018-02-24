<?php

namespace App\Console;

use App\Console\Commands\CreateSchedule;
use App\Console\Commands\ExtendMissingCredits;
use App\Console\Commands\GetCredits;
use App\Console\Commands\ReadUSBList;
use App\Console\Commands\RunReCaller;
use App\Console\Commands\RunCaller;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Cache;

class Kernel extends ConsoleKernel
{
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		RunCaller::class,
		RunReCaller::class,
		GetCredits::class,
		ExtendMissingCredits::class,
		CreateSchedule::class,
		ReadUSBList::class,
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule $schedule
	 *
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{

		$schedule->call(function () {
			if (Cache::get('sys::settings::reset_next-day')) {
				Cache::forever('sys::settings::run_dial', true);
				Cache::forever('sys::settings::run_re-dial', true);
				Cache::forever('sys::settings::check-missing-credits', true);
			}
		})->name('check_for_reset')->dailyAt('3:00');

		$schedule->command(GetCredits::class)
			->name('get_credits')
			->dailyAt('3:30');

//		$schedule->command(ReadUSBList::class)
//			->name('read_usd')
//			->dailyAt('3:45');
//
//		$schedule->command(CreateSchedule::class)
//			->name('create_schedule')
//			->dailyAt('5:00');

		$schedule->command(GetCredits::class, ['yes'])
			->name('get_credits_yes')
			->between('5:50', '23:55')
			->everyFiveMinutes();

//		if (Cache::get('sys::settings::check-missing-credits')) {
//			$schedule->command(ExtendMissingCredits::class)
//				->name('missing_credits')
//				->between('10:00', '23:00')
//				->everyFiveMinutes();
//		}

//		if (Cache::get('sys::settings::run_dial')) {
//			$schedule->command(RunCaller::class)
//				->name('voip_scheduler_caller')
//				->everyMinute()
//				->between('6:00', '23:55')
//				->withoutOverlapping();
//		}
//
//		if (Cache::get('sys::settings::run_re-dial')) {
//			$schedule->command(RunReCaller::class)
//				->name('voip_scheduler_recall')
//				->cron('*/3 * * * *')
//				->between('6:00', '23:55')
//				->withoutOverlapping();
//		}
		
	}

	/**
	 * Register the commands for the application.
	 *
	 * @return void
	 */
	protected function commands()
	{
		$this->load(__DIR__ . '/Commands');

		require base_path('routes/console.php');
	}
}
