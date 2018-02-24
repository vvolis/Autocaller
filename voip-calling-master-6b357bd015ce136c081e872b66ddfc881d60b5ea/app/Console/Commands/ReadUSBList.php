<?php

namespace App\Console\Commands;

use App\Libraries\Modem;
use App\Libraries\Helper;
use Cache;
use Log;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ReadUSBList extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'voip:sys:usb_read';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Cache all USB devices';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{

		if (Cache::get('sys::settings::advanced-logging')) {
			Log::info('[SYS][CRON][READ-USB]');
		}

		$viewDevicesCommand = 'python3 -m serial.tools.list_ports';

		$script = '/usr/bin/python3 ' . base_path('modem.py');
		$getNumberCommandUnix = $script . ' modem get_number %s';

		$process = new Process($viewDevicesCommand);
		$process->run();

		if (!$process->isSuccessful()) {
			Log::critical('[CMD][ERROR][GET_USB_LIST]');
		}

		$credits = Modem::getCredits();

		$usbList = array_filter(explode("\n", $process->getOutput()));
		$usbList = array_map('trim', $usbList);

		$cleanUSBList = [];
		$i = 0;
		$n = 1;

		foreach ($usbList as $item) {
			if ($i % 3 == 0) {

				$getNumberCommand = sprintf($getNumberCommandUnix, $item);

				$process = new Process($getNumberCommand);
				$process->start();
				$process->wait();

				$response = null;
				foreach ($process as $type => $data) {
					if ($process::OUT === $type) {
						$response = $data;
					}
				}
				$process->stop();

				$phone = '+' . Helper::getStringBetween($response, '"+', '",');

				if ($phone == '+') {
					$this->error('[CMD][ERROR][CHECK_SIM_NUMBER][' . $item . '][' . $getNumberCommand . ']');
					Log::critical('[CMD][ERROR][CHECK_SIM_NUMBER][' . $item . '][' . $getNumberCommand . ']');
				} else {
					$this->info('[CMD][SUCCESS][CHECK_SIM_NUMBER][' . $n . '][_/dev/ttyUSB' . ($i + 2) . '][' . $phone . ']');
				}

				$phoneNumber = (int)$phone;

				if (!isset($credits[$phoneNumber])) {
					Log::critical('Could not found USB number : ' . $phoneNumber);
				}
				$cleanUSBList[] = [
					'port'          => 'USB' . ($i + 2),
					'path'          => '/dev/ttyUSB' . ($i + 2),
					'phone'         => $phoneNumber,
					'local'         => Modem::getPhoneLocal($phoneNumber),
					'carrier'       => Modem::getPhoneCarrier($phoneNumber),
					'credits_start' => isset($credits[$phoneNumber]) ? $credits[$phoneNumber]['credits_start'] : 0,
					'credits_limit' => isset($credits[$phoneNumber]) ? $credits[$phoneNumber]['credits_start'] + config('voip.daily-credits') : 0,
				];
				$n++;
			}
			$i++;
		}

		Cache::forever('sys::usb_list', $cleanUSBList);

	}

}
