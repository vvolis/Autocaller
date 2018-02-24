<?php

namespace App\Console\Commands;

use App\Libraries\Helper;
use Cache;
use Log;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DebugModemNumbers extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'voip:sys:debug_usb_numbers';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Check if all devices numbers are available';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{

		$i = 1;
		foreach (Cache::get('sys::usb_list') as $usb) {

			sleep(3);
			$script = '/usr/bin/python3 ' . base_path('modem.py');
			$getNumberCommandUnix = $script . ' modem get_number %s';
			$getNumberCommand = sprintf($getNumberCommandUnix, $usb['path']);

			$process = new Process($getNumberCommand);
			$process->start();
			$process->wait();

			$response = null;
			foreach ($process as $type => $data) {
				if ($process::OUT === $type) {
					$response = $data;
				}
			}

			$phone = Helper::getStringBetween($response, '"+', '",');

			if ($phone == '+') {
				$this->error('[CMD][ERROR][CHECK_SIM_NUMBER][' . $usb['port'] . '][' . $getNumberCommand . ']');
				Log::critical('[CMD][ERROR][CHECK_SIM_NUMBER][' . $usb['port'] . '][' . $getNumberCommand . ']');
			} else {
				$this->info('[CMD][SUCCESS][CHECK_SIM_NUMBER][' . $i . '][' . $usb['port'] . '][' . $phone . ']');
			}

			$i++;

		}

	}

}
