<?php

namespace App\Http\Controllers;

//use Goutte;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client as GuzzleClient;
use App\CallSchedule;
use Cache;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Exception\ProcessFailedException;

class TestController extends Controller
{

	//Max minutes per day (+10 for safety)
	const MAX_TOTAL_SECONDS_PER_DAY = 130 * 60;

	//Min brake seconds between calls
	const MIN_BREAK_SECONDS = 60 * 3;

	//Max break seconds between calls
	const MAX_BREAK_SECONDS = 60 * 35;

	//Max seconds per "working day"
	const MAX_SECONDS_PER_DAY = 60 * 60 * 9;

	//Max seconds per call (33min)
	const MAX_SECONDS_PER_CALL = 60 * 17;

	//Min seconds per call (1min)
	const MIN_SECONDS_PER_CALL = 60 * 1;

	const DAY_START_TIME = '07:00:00';

	const DAY_START_INTERVAL_SECONDS = 60 * 28;

	public function call($port)
	{

		$script = '/usr/bin/python3 ' . base_path('modem.py');
		$callingNumber = '22411141';

		$dialCommand = $script . ' modem call /dev/tty' . $port . ' ' . $callingNumber;

		\Log::info('Sending TEST command :  ' . $dialCommand);
		$process = new Process($dialCommand);
		$process->run();

		dd($process->getOutput());

	}

	public function getStringBetween($string, $start, $end)
	{
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;

		return substr($string, $ini, $len);
	}

	public function number($port)
	{

//		$process = new Process('whoami');
//		$process->run();
//		dd($process->getOutput());

		$res = [];
		$script = '/usr/bin/python3 ' . base_path('modem.py');
		$getNumberCommandUnix = $script . ' modem get_number %s';
		$portUrl = '/dev/tty' . $port;
		$getNumberCommand = sprintf($getNumberCommandUnix, $portUrl);

		$process = new Process($getNumberCommand);
		$process->start();
		$process->wait();

		$response = null;
//		dd($process);
		foreach ($process as $type => $data) {
			if ($process::OUT === 'out') {
				dd([$type, $process, $process::OUT]);
				$response = $data;
			}
		}

		$phone = '+' . $this->getStringBetween($response, '"+', '",');

		dd($phone);

	}

	public function debug()
	{

		$lastStatuses = [];

		$usbList = Cache::get('sys::usb_list');

		dd($usbList);

		foreach ($usbList as $port) {
			$stats = CallSchedule::where('done', false)->where('port', $port['port'])->first();
			if (is_object($stats)) {
				$lastStatuses[$port['port']] = $stats->toArray();
			}
		}

		dd([$usbList, $lastStatuses]);
	}

	public function index()
	{
		dd('test');
	}

	public function crawler()
	{

		$crawlerUrl = env('VOIP_URL');

		$client = new Client();
		$crawler = $client->request('GET', $crawlerUrl);

		$form = $crawler->selectButton('Login')->form();
		$form['lusername'] = env('VOIP_USERNAME');
		$form['lpassword'] = env('VOIP_PASSWORD');


		$client->submit($form);
		$crawler = $client->request('GET', $crawlerUrl);
		$list = [];

		$i = 0;
		$crawler->filter('tr')->each(function ($node) use (&$list, &$i) {

			$i++;
			$c = 0;

			$node->filter('td')->each(function ($node2) use (&$c, &$i, &$list) {
				if ($c == 0) {
					$list[$i]['phone'] = (int)$node2->html();
				} else if ($c == 2) {
					$list[$i]['credits'] = (int)$node2->html();
				}

				$c++;
			});

		});

		$list = collect($list)->keyBy('phone');

	}

}
