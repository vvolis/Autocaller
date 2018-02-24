<?php

namespace App\Libraries;

use App\CallSchedule;
use App\CallZoneUse;
use App\NumberList;
use App\PhoneCallCredits;
use Cache;
use Illuminate\Support\Carbon;

class Modem
{

	public static function getCredits()
	{

		$result = [];

		$lastDayQuery = PhoneCallCredits::latest();

		if ($lastDayQuery->count()) {

			$lastDayQuery = $lastDayQuery->first();

			$result = PhoneCallCredits::where('credits_date', $lastDayQuery->credits_date)->get()->toArray();

			$result = collect($result)->keyBy('phone');

		}

		return $result;

	}

	public static function localPhoneFree($number, $from, $to)
	{
		$from = Carbon::parse($from);
		$to = Carbon::parse($to);

		return !CallSchedule::whereDate('schedule_date', '=', $from->toDateString())
			->where('call_start', '<', $from->toDateTimeString())
			->where('call_end', '>', $to->toDateTimeString())
			->where('phone', $number)
			->count();

	}

	public static function getPhoneLocal($number = null)
	{
		$phone = NumberList::where('phone', $number)->first();

		return (is_object($phone)) ? (bool)$phone['local'] : false;
	}

	public static function getPhoneCarrier($number = null)
	{
		$phone = NumberList::where('phone', $number)->first();

		return (is_object($phone)) ? $phone['carrier'] : null;

	}

	public static function getPhoneDialNumber($number = null)
	{
		$callingNumber = null;

		$phoneNumbers = [];

		$phone = NumberList::where('phone', $number)->first();

		if (is_object($phone)) {

			if ($phone['carrier'] == 'TELE2' || $phone['carrier'] == 'ZAGTEL') {
				$phoneNumbers[] = '+37254102790';
				$phoneNumbers[] = '+37254102792';
				$phoneNumbers[] = '+37254102796';
				$phoneNumbers[] = '+37254102961';
				$phoneNumbers[] = '+37254103533';
				$phoneNumbers[] = '+37254103535';
				$phoneNumbers[] = '+37254103536';
				$phoneNumbers[] = '+33821780404';
				$phoneNumbers[] = '+33821780405';
				$phoneNumbers[] = '+33821780406';
				$phoneNumbers[] = '+33821780410';
				$phoneNumbers[] = '+33821780539';
				$phoneNumbers[] = '+33891060040';
				$phoneNumbers[] = '+33891060043';
				$phoneNumbers[] = '+33891060046';
				$phoneNumbers[] = '+33891060918';
				$phoneNumbers[] = '+33891061366';
			}

			//RAMATA
			if ($phone['carrier'] == '1213BITE1') {
				$phoneNumbers[] = '+38682890720';
				$phoneNumbers[] = '+38682890721';
				$phoneNumbers[] = '+38682890722';
				$phoneNumbers[] = '+38682890723';
				$phoneNumbers[] = '+38682890724';
				$phoneNumbers[] = '+38682890725';
				$phoneNumbers[] = '+38682890726';
				$phoneNumbers[] = '+38682890727';
				$phoneNumbers[] = '+38682890728';
				$phoneNumbers[] = '+38682890729';
				$phoneNumbers[] = '+38682890730';
				$phoneNumbers[] = '+38682890731';
				$phoneNumbers[] = '+38682890732';
				$phoneNumbers[] = '+38682890733';
				$phoneNumbers[] = '+38682890734';
				$phoneNumbers[] = '+38682890735';
				$phoneNumbers[] = '+38682890736';
				$phoneNumbers[] = '+38682890737';
				$phoneNumbers[] = '+38682890738';
				$phoneNumbers[] = '+38682890739';
			}

			if ($phone['carrier'] == '312312BITE2') {
//				$phoneNumbers[] = '+38682890358';
//				$phoneNumbers[] = '+38682890359';
//				$phoneNumbers[] = '+38682890360';
//				$phoneNumbers[] = '+38682890361';
//				$phoneNumbers[] = '+38682890362';
				$phoneNumbers[] = '+38682230475';
				$phoneNumbers[] = '+38682230485';
				$phoneNumbers[] = '+38682230488';
				$phoneNumbers[] = '+38682230494';
				$phoneNumbers[] = '+38682230495';
				$phoneNumbers[] = '+38682230489';
			}

			if ($phone['carrier'] == 'TE123LE2') {
//				$phoneNumbers[] = '+38682890358';
//				$phoneNumbers[] = '+38682890359';
//				$phoneNumbers[] = '+38682890360';
//				$phoneNumbers[] = '+38682890361';
//				$phoneNumbers[] = '+38682890362';
				//
//				$phoneNumbers[] = '+33891060012';
//				$phoneNumbers[] = '+33821782001';
//				$phoneNumbers[] = '+33821780402';
//				$phoneNumbers[] = '+33821782800';
//				$phoneNumbers[] = '+33821782801';
//				$phoneNumbers[] = '+33821782802';
//				$phoneNumbers[] = '+33821782803';
//				$phoneNumbers[] = '+33821782804';
//				$phoneNumbers[] = '+33821782805';
//				$phoneNumbers[] = '+33821782806';
//				$phoneNumbers[] = '+33821782807';
//				$phoneNumbers[] = '+33821782808';
//				$phoneNumbers[] = '+33821782809';
//				$phoneNumbers[] = '+33821782810';
//				$phoneNumbers[] = '+33821782811';
//				$phoneNumbers[] = '+33821782812';
//				$phoneNumbers[] = '+33821782813';
//				$phoneNumbers[] = '+33821782814';
//				$phoneNumbers[] = '+33821782815';
//				$phoneNumbers[] = '+33821782816';
//				$phoneNumbers[] = '+33821782817';
//				$phoneNumbers[] = '+33821782818';
//				$phoneNumbers[] = '+33821782819';
//				$phoneNumbers[] = '+33821782820';
//				$phoneNumbers[] = '+33821782821';
//				$phoneNumbers[] = '+33821782822';
//				$phoneNumbers[] = '+33821782823';
//				$phoneNumbers[] = '+33821782824';
//				$phoneNumbers[] = '+33821782825';
//				$phoneNumbers[] = '+33821782826';
//				$phoneNumbers[] = '+33821782827';
//				$phoneNumbers[] = '+33821782828';
//				$phoneNumbers[] = '+33821782829';
//				$phoneNumbers[] = '+33821782001';
//				$phoneNumbers[] = '+33821782002';
//				$phoneNumbers[] = '+33821782005';
//				$phoneNumbers[] = '+33821782006';
//				$phoneNumbers[] = '+33821782007';
//				$phoneNumbers[] = '+33821782009';
//				$phoneNumbers[] = '+33821782016';
//				$phoneNumbers[] = '+33821782020';
//				$phoneNumbers[] = '+33821782021';
//				$phoneNumbers[] = '+33821782026';
//				$phoneNumbers[] = '+33891060007';
//				$phoneNumbers[] = '+33891060008';
//				$phoneNumbers[] = '+33891060009';
//				$phoneNumbers[] = '+33891060011';
//				$phoneNumbers[] = '+33891060012';
//				$phoneNumbers[] = '+33891060021';
//				$phoneNumbers[] = '+33891060022';
//				$phoneNumbers[] = '+33891060025';
//				$phoneNumbers[] = '+33891060027';
//				$phoneNumbers[] = '+33891060029';
//				$phoneNumbers[] = '+33891060037';
//				$phoneNumbers[] = '+33891060376';
//				$phoneNumbers[] = '+33891060378';
//				$phoneNumbers[] = '+33891060386';
//				$phoneNumbers[] = '+33891060403';
//				$phoneNumbers[] = '+33891060919';
//				$phoneNumbers[] = '+33891061329';
//				$phoneNumbers[] = '+33891061331';
//				$phoneNumbers[] = '+33891061332';
//				$phoneNumbers[] = '+33891061333';
//				$phoneNumbers[] = '+33891061334';

				$phoneNumbers[] = '+33891061329';
				$phoneNumbers[] = '+33891061331';
				$phoneNumbers[] = '+33891061332';
				$phoneNumbers[] = '+33891061333';
				$phoneNumbers[] = '+33891061334';
				$phoneNumbers[] = '+33821782001';
				$phoneNumbers[] = '+33821782002';
				$phoneNumbers[] = '+33821782005';
				$phoneNumbers[] = '+33821782006';
				$phoneNumbers[] = '+33821782007';
				$phoneNumbers[] = '+33821782009';
				$phoneNumbers[] = '+33821782016';
				$phoneNumbers[] = '+33821782020';
				$phoneNumbers[] = '+33821782021';
				$phoneNumbers[] = '+33821782026';

//				$phoneNumbers[] = '+38682230475';
//				$phoneNumbers[] = '+38682230485';
//				$phoneNumbers[] = '+38682230488';
//				$phoneNumbers[] = '+38682230494';
//				$phoneNumbers[] = '+38682230495';
//				$phoneNumbers[] = '+38682230489';
			}

			if (count($phoneNumbers) == 0) {
				dd('Number : ' . $number . ' .Carrier : ' . $phone['carrier'] . 'empty array');
				$callingNumber = null;
			} else {
				$k = array_rand($phoneNumbers);
				$callingNumber = $phoneNumbers[$k];
			}


		}

		return $callingNumber;
	}

	public static function lock($port)
	{
		Cache::forever('usb::' . $port, true);
	}

	public static function unlock($port)
	{
		Cache::forget('usb::' . $port);
	}

	public static function inUse($port)
	{
		return Cache::get('usb::' . $port);
	}

	public static function canUse($port)
	{
		return !Cache::get('usb::' . $port);
	}

}
