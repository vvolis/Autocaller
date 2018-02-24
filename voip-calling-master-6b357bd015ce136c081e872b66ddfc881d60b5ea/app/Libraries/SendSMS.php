<?php

namespace App\Libraries;

use Mail;

class SendSMS
{

	public static function sendStats()
	{

		$message = 'Stats on 12:12:12 777 credits';

		self::sendSMS($message);

	}

	private static function sendSMS($message)
	{

		Mail::send('mail-template', ['content' => $message], function ($msg) {
//			$msg->to(['rolands@cococore.co']);
			$msg->to(['roleeks2@sms.lmt.lv']);
			$msg->from(['info@cococore.co']);
			$msg->subject('SMS STATS');

		});

	}

}
